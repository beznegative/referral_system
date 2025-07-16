<?php
require_once 'includes/database.php';

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
    sendResponse(['error' => 'Метод не поддерживается'], 405);
}

try {
    // Получаем всех партнеров из базы данных
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.telegram_username, u.telegram_id,
               (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count,
               u.paid_for_referrals, u.created_at
        FROM users u 
        WHERE u.is_affiliate = 1 
        ORDER BY u.full_name ASC
    ");
    $stmt->execute();
    $affiliates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Форматируем данные для ответа
    $formattedAffiliates = [];
    foreach ($affiliates as $affiliate) {
        $formattedAffiliates[] = [
            'id' => $affiliate['id'],
            'full_name' => $affiliate['full_name'],
            'telegram_username' => $affiliate['telegram_username'],
            'telegram_id' => $affiliate['telegram_id'],
            'referral_count' => intval($affiliate['referral_count']),
            'paid_for_referrals' => floatval($affiliate['paid_for_referrals']),
            'created_at' => $affiliate['created_at']
        ];
    }
    
    // Отправляем ответ
    sendResponse([
        'success' => true,
        'affiliates' => $formattedAffiliates,
        'total_count' => count($formattedAffiliates)
    ]);
    
} catch (PDOException $e) {
    // Ошибка базы данных
    error_log('Ошибка БД в get_affiliates_api.php: ' . $e->getMessage());
    sendResponse(['error' => 'Ошибка сервера'], 500);
    
} catch (Exception $e) {
    // Другие ошибки
    error_log('Ошибка в get_affiliates_api.php: ' . $e->getMessage());
    sendResponse(['error' => 'Неизвестная ошибка'], 500);
}
?> 