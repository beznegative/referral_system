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
    sendResponse(['success' => false, 'error' => 'Метод не поддерживается'], 405);
}

try {
    // Проверяем соединение с базой данных
    if (!isset($pdo)) {
        throw new Exception('Нет соединения с базой данных');
    }
    
    // Получаем всех партнеров из базы данных
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.telegram_username, u.telegram_id,
               (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count,
               u.paid_for_referrals, u.created_at
        FROM users u 
        WHERE u.is_affiliate = 1 AND u.full_name IS NOT NULL AND u.full_name != ''
        ORDER BY u.full_name ASC
    ");
    
    if (!$stmt) {
        throw new Exception('Ошибка подготовки запроса');
    }
    
    $stmt->execute();
    $affiliates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Проверяем, что данные получены корректно
    if ($affiliates === false) {
        throw new Exception('Ошибка получения данных из БД');
    }
    
    // Форматируем данные для ответа
    $formattedAffiliates = [];
    foreach ($affiliates as $affiliate) {
        // Дополнительная проверка данных
        if (!isset($affiliate['id']) || !isset($affiliate['full_name'])) {
            continue;
        }
        
        $formattedAffiliates[] = [
            'id' => intval($affiliate['id']),
            'full_name' => trim($affiliate['full_name']),
            'telegram_username' => trim($affiliate['telegram_username'] ?? ''),
            'telegram_id' => trim($affiliate['telegram_id'] ?? ''),
            'referral_count' => intval($affiliate['referral_count'] ?? 0),
            'paid_for_referrals' => floatval($affiliate['paid_for_referrals'] ?? 0),
            'created_at' => $affiliate['created_at'] ?? ''
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
    sendResponse(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()], 500);
    
} catch (Exception $e) {
    // Другие ошибки
    error_log('Ошибка в get_affiliates_api.php: ' . $e->getMessage());
    sendResponse(['success' => false, 'error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
}
?> 