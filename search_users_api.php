<?php
require_once 'includes/database.php';
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
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Функция для отправки JSON ответа
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['success' => false, 'error' => 'Метод не поддерживается'], 405);
}

try {
    // Получаем поисковый запрос
    $search = trim($_GET['search'] ?? '');
    
    // Проверяем соединение с базой данных
    if (!isset($pdo)) {
        throw new Exception('Нет соединения с базой данных');
    }
    
    if (empty($search)) {
        // Если поиск пустой, возвращаем пустой результат
        sendResponse([
            'success' => true,
            'users' => [],
            'total_count' => 0
        ]);
    }
    
    // Убираем @ из начала поиска если есть
    $searchTerm = ltrim($search, '@');
    
    // Ищем пользователей по имени или telegram username
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.telegram_username, u.telegram_id, u.is_affiliate,
               (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count,
               u.total_paid_for_referrals, u.created_at
        FROM users u 
        WHERE (u.full_name LIKE :search1 OR u.telegram_username LIKE :search2)
          AND u.full_name IS NOT NULL AND u.full_name != ''
        ORDER BY 
            CASE WHEN u.is_affiliate = 1 THEN 0 ELSE 1 END,
            u.full_name ASC
        LIMIT 10
    ");
    
    if (!$stmt) {
        throw new Exception('Ошибка подготовки запроса');
    }
    
    $searchPattern = '%' . $searchTerm . '%';
    $stmt->bindParam(':search1', $searchPattern, PDO::PARAM_STR);
    $stmt->bindParam(':search2', $searchPattern, PDO::PARAM_STR);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Проверяем, что данные получены корректно
    if ($users === false) {
        throw new Exception('Ошибка получения данных из БД');
    }
    
    // Форматируем данные для ответа
    $formattedUsers = [];
    foreach ($users as $user) {
        // Дополнительная проверка данных
        if (!isset($user['id']) || !isset($user['full_name'])) {
            continue;
        }
        
        $formattedUsers[] = [
            'id' => intval($user['id']),
            'full_name' => trim($user['full_name']),
            'telegram_username' => trim($user['telegram_username'] ?? ''),
            'telegram_id' => trim($user['telegram_id'] ?? ''),
            'is_affiliate' => (bool)$user['is_affiliate'],
            'referral_count' => intval($user['referral_count'] ?? 0),
            'paid_for_referrals' => floatval($user['total_paid_for_referrals'] ?? 0),
            'created_at' => $user['created_at'] ?? ''
        ];
    }
    
    // Отправляем ответ
    sendResponse([
        'success' => true,
        'users' => $formattedUsers,
        'total_count' => count($formattedUsers)
    ]);
    
} catch (PDOException $e) {
    // Ошибка базы данных
    error_log('Ошибка БД в search_users_api.php: ' . $e->getMessage());
    sendResponse(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()], 500);
    
} catch (Exception $e) {
    // Другие ошибки
    error_log('Ошибка в search_users_api.php: ' . $e->getMessage());
    sendResponse(['success' => false, 'error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
}
?> 