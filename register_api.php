<?php
require_once 'includes/database.php';
require_once 'referral_calculator.php';
require_once 'check_captcha.php';

// Проверка капчи отключена, поскольку она уже проверяется на уровне страницы
// Это избегает проблем с сессиями и AJAX запросами
/*
$captchaStatus = checkCaptchaStatus();
if (!$captchaStatus['verified']) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Требуется прохождение капчи',
        'redirect_url' => 'captcha.php?target=miniapp'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
*/

// Устанавливаем заголовки для API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Функция для отправки JSON ответа
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Функция для валидации данных
function validateRegistrationData($data) {
    $errors = [];
    
    // Проверка ФИО
    if (empty($data['full_name'])) {
        $errors[] = 'ФИО обязательно для заполнения';
    } elseif (strlen($data['full_name']) < 2) {
        $errors[] = 'ФИО должно содержать минимум 2 символа';
    }
    
    // Проверка Telegram username
    if (empty($data['telegram_username'])) {
        $errors[] = 'Имя пользователя Telegram обязательно';
    } elseif (strlen(str_replace('@', '', $data['telegram_username'])) < 2) {
        $errors[] = 'Имя пользователя Telegram должно содержать минимум 2 символа';
    }
    
    // Проверка telegram_id
    if (empty($data['telegram_id'])) {
        $errors[] = 'Telegram ID обязателен';
    } elseif (!is_numeric($data['telegram_id'])) {
        $errors[] = 'Некорректный Telegram ID';
    }
    
    return $errors;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Метод не поддерживается'], 405);
}

try {
    // Получаем данные из формы
    $data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'telegram_username' => trim($_POST['telegram_username'] ?? ''),
        'telegram_id' => trim($_POST['telegram_id'] ?? ''),
        'affiliate_id' => !empty($_POST['affiliate_id']) ? intval($_POST['affiliate_id']) : null
    ];
    
    // Валидация данных
    $errors = validateRegistrationData($data);
    if (!empty($errors)) {
        sendResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
    }
    
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // Проверяем, не существует ли уже пользователь с таким telegram_id
    $stmt = $pdo->prepare("SELECT id FROM users WHERE telegram_id = ?");
    $stmt->execute([$data['telegram_id']]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        sendResponse(['success' => false, 'message' => 'Пользователь с таким Telegram ID уже существует'], 409);
    }
    
    // Если указан affiliate_id, проверяем, что пользователь существует (может быть любой пользователь)
    if ($data['affiliate_id']) {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
        if (!$stmt) {
            $pdo->rollBack();
            sendResponse(['success' => false, 'message' => 'Ошибка подготовки запроса'], 500);
        }
        
        $stmt->execute([$data['affiliate_id']]);
        $affiliateData = $stmt->fetch();
        
        if (!$affiliateData) {
            $pdo->rollBack();
            sendResponse(['success' => false, 'message' => 'Указанный пригласитель не найден'], 400);
        }
    }
    

    
    // Создаем нового пользователя
    $stmt = $pdo->prepare("
        INSERT INTO users (
            full_name, telegram_username, telegram_id, 
            is_affiliate, affiliate_id,
            total_paid_amount, total_paid_for_referrals
        ) VALUES (?, ?, ?, 0, ?, 0.00, 0.00)
    ");
    
    $stmt->execute([
        $data['full_name'],
        $data['telegram_username'],
        $data['telegram_id'],
        $data['affiliate_id']
    ]);
    
    $newUserId = $pdo->lastInsertId();
    
    // Если есть пригласитель, обновляем счетчики рефералов
    if ($data['affiliate_id']) {
        try {
            // Обновляем количество рефералов у пригласителя
            $stmt = $pdo->prepare("
                UPDATE users 
                SET referral_count = (
                    SELECT COUNT(*) FROM users u2 WHERE u2.affiliate_id = users.id
                ) 
                WHERE id = ?
            ");
            
            if (!$stmt) {
                throw new Exception('Ошибка подготовки запроса обновления счетчиков');
            }
            
            $stmt->execute([$data['affiliate_id']]);
            
            // Обновляем всю цепочку рефералов (максимум 10 уровней для защиты от зацикливания)
            $currentAffiliateId = $data['affiliate_id'];
            $level = 0;
            
            while ($currentAffiliateId && $level < 10) {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET referral_count = (
                        SELECT COUNT(*) FROM users u2 WHERE u2.affiliate_id = users.id
                    ) 
                    WHERE id = ?
                ");
                
                if (!$stmt) {
                    throw new Exception('Ошибка подготовки запроса обновления цепочки');
                }
                
                $stmt->execute([$currentAffiliateId]);
                
                // Получаем следующий уровень
                $stmt = $pdo->prepare("SELECT affiliate_id FROM users WHERE id = ?");
                if (!$stmt) {
                    break;
                }
                
                $stmt->execute([$currentAffiliateId]);
                $result = $stmt->fetch();
                $currentAffiliateId = $result ? $result['affiliate_id'] : null;
                $level++;
            }
            
        } catch (Exception $e) {
            // Логируем ошибку, но не прерываем регистрацию
            error_log('Ошибка обновления счетчиков рефералов: ' . $e->getMessage());
        }
    }
    
    // Подтверждаем транзакцию
    $pdo->commit();
    
    // Отправляем успешный ответ
    sendResponse([
        'success' => true,
        'message' => 'Регистрация прошла успешно!',
        'user_id' => $newUserId
    ]);
    
} catch (PDOException $e) {
    // Откатываем транзакцию при ошибке БД
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Ошибка БД в register_api.php: ' . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()], 500);
    
} catch (Exception $e) {
    // Откатываем транзакцию при любой другой ошибке
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Ошибка в register_api.php: ' . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
}
?> 