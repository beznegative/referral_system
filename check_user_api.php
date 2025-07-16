<?php
require_once 'includes/database.php';

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

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Метод не поддерживается'], 405);
}

try {
    // Получаем данные из запроса
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['telegram_id'])) {
        sendResponse(['error' => 'Не указан telegram_id'], 400);
    }
    
    $telegramId = $input['telegram_id'];
    
    // Проверяем, что telegram_id является числом
    if (!is_numeric($telegramId)) {
        sendResponse(['error' => 'Некорректный telegram_id'], 400);
    }
    
    // Ищем пользователя в базе данных
    $stmt = $pdo->prepare("
        SELECT id, full_name, telegram_username, telegram_id, is_affiliate, 
               paid_amount, paid_for_referrals, referral_count, created_at
        FROM users 
        WHERE telegram_id = ?
    ");
    $stmt->execute([$telegramId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Пользователь найден
        sendResponse([
            'exists' => true,
            'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'telegram_username' => $user['telegram_username'],
                'telegram_id' => $user['telegram_id'],
                'is_affiliate' => (bool)$user['is_affiliate'],
                'paid_amount' => floatval($user['paid_amount']),
                'paid_for_referrals' => floatval($user['paid_for_referrals']),
                'referral_count' => intval($user['referral_count']),
                'created_at' => $user['created_at']
            ]
        ]);
    } else {
        // Пользователь не найден
        sendResponse([
            'exists' => false,
            'user' => null
        ]);
    }
    
} catch (PDOException $e) {
    // Ошибка базы данных
    error_log('Ошибка БД в check_user_api.php: ' . $e->getMessage());
    sendResponse(['error' => 'Ошибка сервера'], 500);
    
} catch (Exception $e) {
    // Другие ошибки
    error_log('Ошибка в check_user_api.php: ' . $e->getMessage());
    sendResponse(['error' => 'Неизвестная ошибка'], 500);
}
?> 