<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'includes/database.php';

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Недопустимый метод запроса']);
    exit;
}

// Получаем данные
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
$payment_month = isset($_POST['payment_month']) ? trim($_POST['payment_month']) : null;

// Проверяем обязательные параметры
if (!$user_id || !$payment_month) {
    echo json_encode(['success' => false, 'error' => 'Отсутствуют обязательные параметры']);
    exit;
}

// Проверяем формат месяца (YYYY-MM)
if (!preg_match('/^\d{4}-\d{2}$/', $payment_month)) {
    echo json_encode(['success' => false, 'error' => 'Неверный формат месяца']);
    exit;
}

try {
    // Получаем данные за указанный месяц из таблицы monthly_payments
    $stmt = $pdo->prepare("
        SELECT 
            paid_amount as monthly_paid_amount,
            paid_for_referrals as monthly_paid_for_referrals
        FROM monthly_payments 
        WHERE user_id = ? AND payment_month = ?
    ");
    $stmt->execute([$user_id, $payment_month]);
    $monthly_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Если данных за этот месяц нет, используем значения по умолчанию
    $monthly_paid_amount = $monthly_data ? floatval($monthly_data['monthly_paid_amount']) : 0.00;
    $monthly_paid_for_referrals = $monthly_data ? floatval($monthly_data['monthly_paid_for_referrals']) : 0.00;
    
    // Рассчитываем общие суммы: все данные из monthly_payments включая текущий месяц
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(paid_amount), 0) as total_paid_amount,
            COALESCE(SUM(paid_for_referrals), 0) as total_paid_for_referrals
        FROM monthly_payments 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Если данных за текущий месяц ещё нет, добавляем их к расчёту
    if (!$monthly_data) {
        $totals['total_paid_amount'] += $monthly_paid_amount;
        $totals['total_paid_for_referrals'] += $monthly_paid_for_referrals;
    }
    
    $total_paid_amount = floatval($totals['total_paid_amount']);
    $total_paid_for_referrals = floatval($totals['total_paid_for_referrals']);
    
    // Форматируем суммы
    $response = [
        'success' => true,
        'monthly_paid_amount' => number_format($monthly_paid_amount, 2, '.', ''),
        'monthly_paid_for_referrals' => number_format($monthly_paid_for_referrals, 2, '.', ''),
        'total_paid_amount' => number_format($total_paid_amount, 2, '.', ' '),
        'total_paid_for_referrals' => number_format($total_paid_for_referrals, 2, '.', ' ')
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Ошибка в get_monthly_data.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ошибка получения данных: ' . $e->getMessage()]);
}
?>
