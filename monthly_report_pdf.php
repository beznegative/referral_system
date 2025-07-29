<?php
require_once 'includes/database.php';
require_once 'referral_calculator.php';

// Проверяем, что месяц указан
if (!isset($_GET['month'])) {
    die('Не указан месяц для отчета');
}

$month = $_GET['month'];

try {
    // Получаем данные для отчета
    // Текущие месячные выплаты
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.telegram_username, u.is_affiliate,
               u.monthly_paid_amount, u.monthly_paid_for_referrals,
               (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count
        FROM users u 
        WHERE u.payment_month = ? AND (u.monthly_paid_amount > 0 OR u.monthly_paid_for_referrals > 0)
        ORDER BY u.is_affiliate DESC, u.monthly_paid_for_referrals DESC, u.monthly_paid_amount DESC
    ");
    $stmt->execute([$month]);
    $current_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Архивные данные (если есть)
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.telegram_username, u.is_affiliate,
               mp.paid_amount, mp.paid_for_referrals,
               (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count
        FROM monthly_payments mp
        JOIN users u ON mp.user_id = u.id
        WHERE mp.payment_month = ?
        ORDER BY u.is_affiliate DESC, mp.paid_for_referrals DESC, mp.paid_amount DESC
    ");
    $stmt->execute([$month]);
    $archived_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Объединяем данные, приоритет архивным
    $payments_data = [];
    $processed_users = [];
    
    // Сначала архивные данные
    foreach ($archived_payments as $payment) {
        $payments_data[] = [
            'full_name' => $payment['full_name'],
            'telegram_username' => $payment['telegram_username'],
            'is_affiliate' => $payment['is_affiliate'],
            'paid_amount' => $payment['paid_amount'],
            'paid_for_referrals' => $payment['paid_for_referrals'],
            'referral_count' => $payment['referral_count'],
            'source' => 'archive'
        ];
        $processed_users[] = $payment['telegram_username'];
    }
    
    // Затем текущие данные (только если пользователя еще нет)
    foreach ($current_payments as $payment) {
        if (!in_array($payment['telegram_username'], $processed_users)) {
            $payments_data[] = [
                'full_name' => $payment['full_name'],
                'telegram_username' => $payment['telegram_username'],
                'is_affiliate' => $payment['is_affiliate'],
                'paid_amount' => $payment['monthly_paid_amount'],
                'paid_for_referrals' => $payment['monthly_paid_for_referrals'],
                'referral_count' => $payment['referral_count'],
                'source' => 'current'
            ];
        }
    }
    
    // Статистика
    $total_paid = array_sum(array_column($payments_data, 'paid_amount'));
    $total_paid_referrals = array_sum(array_column($payments_data, 'paid_for_referrals'));
    $affiliates_count = count(array_filter($payments_data, function($p) { return $p['is_affiliate'] == 1; }));
    $users_count = count(array_filter($payments_data, function($p) { return $p['is_affiliate'] == 0; }));
    
} catch (Exception $e) {
    die('Ошибка получения данных: ' . htmlspecialchars($e->getMessage()));
}

// Форматируем дату для заголовка
$month_formatted = date('F Y', strtotime($month . '-01'));
$month_ru = [
    'January' => 'Январь', 'February' => 'Февраль', 'March' => 'Март',
    'April' => 'Апрель', 'May' => 'Май', 'June' => 'Июнь',
    'July' => 'Июль', 'August' => 'Август', 'September' => 'Сентябрь',
    'October' => 'Октябрь', 'November' => 'Ноябрь', 'December' => 'Декабрь'
];
foreach ($month_ru as $en => $ru) {
    $month_formatted = str_replace($en, $ru, $month_formatted);
}

// Устанавливаем заголовки для скачивания PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчет за <?= htmlspecialchars($month_formatted) ?></title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #2c5282;
        }
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 11px;
        }
        td {
            font-size: 10px;
        }
        .affiliate {
            background-color: #e8f4fd;
        }
        .user {
            background-color: #f9f9f9;
        }
        .total-row {
            background-color: #d4edda;
            font-weight: bold;
        }
        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        @media print {
            .print-button { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Печать / Сохранить PDF</button>
    
    <div class="header">
        <h1>Отчет по выплатам за <?= htmlspecialchars($month_formatted) ?></h1>
        <p>Сгенерирован: <?= date('d.m.Y H:i') ?></p>
    </div>
    
    <div class="stats">
        <div class="stat-item">
            <div class="stat-value"><?= number_format($total_paid, 2, '.', ' ') ?> ₽</div>
            <div class="stat-label">Всего выплачено</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= number_format($total_paid_referrals, 2, '.', ' ') ?> ₽</div>
            <div class="stat-label">За рефералов</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $affiliates_count ?></div>
            <div class="stat-label">Партнеров</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $users_count ?></div>
            <div class="stat-label">Пользователей</div>
        </div>
    </div>
    
    <?php if (!empty($payments_data)): ?>
    <table>
        <thead>
            <tr>
                <th>№</th>
                <th>ФИО</th>
                <th>Telegram</th>
                <th>Тип</th>
                <th>Рефералов</th>
                <th>Выплачено</th>
                <th>За рефералов</th>
                <th>Итого</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments_data as $index => $payment): ?>
            <tr class="<?= $payment['is_affiliate'] ? 'affiliate' : 'user' ?>">
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($payment['full_name']) ?></td>
                <td><?= htmlspecialchars($payment['telegram_username']) ?></td>
                <td><?= $payment['is_affiliate'] ? 'Партнер' : 'Пользователь' ?></td>
                <td><?= $payment['referral_count'] ?></td>
                <td><?= number_format($payment['paid_amount'], 2, '.', ' ') ?> ₽</td>
                <td><?= number_format($payment['paid_for_referrals'], 2, '.', ' ') ?> ₽</td>
                <td><?= number_format($payment['paid_amount'] + $payment['paid_for_referrals'], 2, '.', ' ') ?> ₽</td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="5"><strong>ИТОГО:</strong></td>
                <td><strong><?= number_format($total_paid, 2, '.', ' ') ?> ₽</strong></td>
                <td><strong><?= number_format($total_paid_referrals, 2, '.', ' ') ?> ₽</strong></td>
                <td><strong><?= number_format($total_paid + $total_paid_referrals, 2, '.', ' ') ?> ₽</strong></td>
            </tr>
        </tbody>
    </table>
    <?php else: ?>
    <div style="text-align: center; padding: 50px; color: #666;">
        <h3>Нет данных за выбранный месяц</h3>
        <p>За <?= htmlspecialchars($month_formatted) ?> не было произведено выплат</p>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 50px; border-top: 1px solid #ddd; padding-top: 20px; font-size: 10px; color: #666;">
        <p><strong>Примечания:</strong></p>
        <ul>
            <li>Партнеры выделены голубым цветом</li>
            <li>Пользователи выделены серым цветом</li>
            <li>Данные включают как текущие месячные выплаты, так и архивные записи</li>
            <li>Для сохранения в PDF используйте функцию печати браузера</li>
        </ul>
    </div>
</body>
</html> 