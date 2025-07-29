<?php
require_once 'includes/database.php';
require_once 'referral_calculator.php';
require_once 'check_captcha.php';

// Временно отключаем проверку капчи для тестирования
// TODO: Включить обратно после тестирования
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

// Ключ шифрования (должен быть таким же, как в других файлах)
define('ENCRYPTION_KEY', 'your-secret-key-123');

// Функция для отправки JSON ответа
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Функция для шифрования данных
function encryptData($data) {
    if (empty($data)) {
        return '';
    }
    
    $method = "AES-256-CBC";
    $key = substr(hash('sha256', ENCRYPTION_KEY, true), 0, 32);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
    
    if ($encrypted === false) {
        return '';
    }
    
    return base64_encode($iv . $encrypted);
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
    
    // Проверка банковской карты (смягченная проверка)
    if (empty($data['bank_card'])) {
        $errors[] = 'Номер банковской карты обязателен';
    } else {
        $cardNumber = preg_replace('/\s+/', '', $data['bank_card']);
        $cardDigits = preg_replace('/[^0-9]/', '', $cardNumber);
        if (strlen($cardDigits) < 13 || strlen($cardDigits) > 19) {
            $errors[] = 'Номер банковской карты должен содержать от 13 до 19 цифр';
        }
    }
    
    // Проверка Telegram username (смягченная проверка)
    if (empty($data['telegram_username'])) {
        $errors[] = 'Имя пользователя Telegram обязательно';
    } elseif (strlen(str_replace('@', '', $data['telegram_username'])) < 2) {
        $errors[] = 'Имя пользователя Telegram должно содержать минимум 2 символа';
    }
    
    // Проверка номера телефона (смягченная проверка)
    if (empty($data['phone_number'])) {
        $errors[] = 'Номер телефона обязателен';
    } elseif (strlen(preg_replace('/[^0-9]/', '', $data['phone_number'])) < 10) {
        $errors[] = 'Номер телефона должен содержать минимум 10 цифр';
    }
    
    // Проверка даты рождения (смягченная проверка)
    if (empty($data['birth_date'])) {
        $errors[] = 'Дата рождения обязательна';
    } else {
        $birthDate = DateTime::createFromFormat('Y-m-d', $data['birth_date']);
        if (!$birthDate) {
            $errors[] = 'Некорректная дата рождения';
        } else {
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            if ($age > 120) {
                $errors[] = 'Некорректная дата рождения';
            }
        }
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
        'bank_card' => trim($_POST['bank_card'] ?? ''),
        'telegram_username' => trim($_POST['telegram_username'] ?? ''),
        'phone_number' => trim($_POST['phone_number'] ?? ''),
        'birth_date' => trim($_POST['birth_date'] ?? ''),
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
    
    // Проверяем, не существует ли уже пользователь с таким номером телефона
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
    $stmt->execute([$data['phone_number']]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        sendResponse(['success' => false, 'message' => 'Пользователь с таким номером телефона уже существует'], 409);
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
    
    // Шифруем банковскую карту
    $encryptedBankCard = encryptData($data['bank_card']);
    if (empty($encryptedBankCard)) {
        $pdo->rollBack();
        sendResponse(['success' => false, 'message' => 'Ошибка шифрования данных'], 500);
    }
    
    // Создаем нового пользователя
    $stmt = $pdo->prepare("
        INSERT INTO users (
            full_name, bank_card, telegram_username, telegram_id, 
            phone_number, birth_date, is_affiliate, affiliate_id,
            total_paid_amount, total_paid_for_referrals, referral_count
        ) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 0.00, 0.00, 0)
    ");
    
    $stmt->execute([
        $data['full_name'],
        $encryptedBankCard,
        $data['telegram_username'],
        $data['telegram_id'],
        $data['phone_number'],
        $data['birth_date'],
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
    sendResponse(['success' => false, 'message' => 'Ошибка сервера'], 500);
    
} catch (Exception $e) {
    // Откатываем транзакцию при любой другой ошибке
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Ошибка в register_api.php: ' . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Неизвестная ошибка'], 500);
}
?> 