<?php
// Подключение к базе данных
require_once 'includes/database.php';

// Функция для экспорта в CSV
function exportToCSV($data, $filename, $headers) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    $output = fopen('php://output', 'w');
    
    // Записываем заголовки
    fputcsv($output, $headers);
    
    // Записываем данные
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
}

// Определяем тип экспорта
$type = isset($_GET['type']) ? $_GET['type'] : 'users';

try {
    switch ($type) {
        case 'users':
            // Экспорт пользователей
            $stmt = $pdo->query("
                SELECT u.full_name, u.telegram_username, u.telegram_id, u.phone_number, 
                       u.birth_date, u.created_at, u.paid_amount, u.paid_for_referrals,
                       a.full_name as affiliate_name
                FROM users u
                LEFT JOIN users a ON u.affiliate_id = a.id
                WHERE u.is_affiliate = 0
                ORDER BY u.created_at DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = [
                'ФИО',
                'Telegram',
                'Telegram ID',
                'Телефон',
                'Дата рождения',
                'Дата регистрации',
                'Выплачено',
                'Выплачено за рефералов',
                'Партнер'
            ];
            
            $exportData = [];
            foreach ($data as $row) {
                $exportData[] = [
                    $row['full_name'],
                    $row['telegram_username'],
                    $row['telegram_id'],
                    $row['phone_number'],
                    $row['birth_date'],
                    $row['created_at'],
                    $row['paid_amount'],
                    $row['paid_for_referrals'],
                    $row['affiliate_name'] ?: '-'
                ];
            }
            
            exportToCSV($exportData, 'users_' . date('Y-m-d'), $headers);
            break;
            
        case 'affiliates':
            // Экспорт партнеров
            $stmt = $pdo->query("
                SELECT u.full_name, u.telegram_username, u.telegram_id, u.phone_number, 
                       u.birth_date, u.created_at, u.paid_amount, u.paid_for_referrals,
                       (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count
                FROM users u
                WHERE u.is_affiliate = 1
                ORDER BY referral_count DESC, u.created_at DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = [
                'ФИО',
                'Telegram',
                'Telegram ID',
                'Телефон',
                'Дата рождения',
                'Дата регистрации',
                'Выплачено',
                'Выплачено за рефералов',
                'Количество рефералов'
            ];
            
            $exportData = [];
            foreach ($data as $row) {
                $exportData[] = [
                    $row['full_name'],
                    $row['telegram_username'],
                    $row['telegram_id'],
                    $row['phone_number'],
                    $row['birth_date'],
                    $row['created_at'],
                    $row['paid_amount'],
                    $row['paid_for_referrals'],
                    $row['referral_count']
                ];
            }
            
            exportToCSV($exportData, 'affiliates_' . date('Y-m-d'), $headers);
            break;
            
        case 'payments':
            // Экспорт выплат
            $stmt = $pdo->query("
                SELECT u.full_name, u.telegram_username, u.paid_amount, u.paid_for_referrals,
                       u.is_affiliate, u.created_at,
                       (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count
                FROM users u
                WHERE u.paid_amount > 0 OR u.paid_for_referrals > 0
                ORDER BY (u.paid_amount + u.paid_for_referrals) DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = [
                'ФИО',
                'Telegram',
                'Выплачено',
                'Выплачено за рефералов',
                'Общая сумма',
                'Тип пользователя',
                'Количество рефералов',
                'Дата регистрации'
            ];
            
            $exportData = [];
            foreach ($data as $row) {
                $total = $row['paid_amount'] + $row['paid_for_referrals'];
                $exportData[] = [
                    $row['full_name'],
                    $row['telegram_username'],
                    $row['paid_amount'],
                    $row['paid_for_referrals'],
                    $total,
                    $row['is_affiliate'] ? 'Партнер' : 'Пользователь',
                    $row['referral_count'],
                    $row['created_at']
                ];
            }
            
            exportToCSV($exportData, 'payments_' . date('Y-m-d'), $headers);
            break;
            
        default:
            header('Location: settings.php?error=invalid_export_type');
            exit;
    }
    
} catch (Exception $e) {
    header('Location: settings.php?error=' . urlencode($e->getMessage()));
    exit;
}
?> 