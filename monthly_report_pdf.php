<?php
require_once 'includes/database.php';
require_once 'referral_calculator.php';

// Определяем тип отчета
$report_type = $_GET['type'] ?? 'monthly';
$format = $_GET['format'] ?? 'pdf';

try {
    if ($report_type === 'all_time') {
        generateAllTimeReport($pdo, $format);
    } elseif ($report_type === 'top_performers') {
        generateTopPerformersReport($pdo, $format);
    } else {
        // Проверяем, что месяц указан для месячного отчета
        if (!isset($_GET['month'])) {
            die('Не указан месяц для отчета');
        }
        generateMonthlyReport($pdo, $_GET['month'], $format);
    }
} catch (Exception $e) {
    die('Ошибка генерации отчета: ' . $e->getMessage());
}

/**
 * Генерация месячного отчета
 */
function generateMonthlyReport($pdo, $month, $format) {
    // Получаем данные из monthly_payments за указанный месяц
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.telegram_username, u.is_affiliate,
               mp.paid_amount, mp.paid_for_referrals,
               a.full_name as affiliate_name,
               (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count
        FROM monthly_payments mp
        JOIN users u ON mp.user_id = u.id
        LEFT JOIN users a ON u.affiliate_id = a.id
        WHERE mp.payment_month = ?
        ORDER BY u.is_affiliate DESC, mp.paid_for_referrals DESC, mp.paid_amount DESC
    ");
    $stmt->execute([$month]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем статистику за месяц
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_users,
            SUM(mp.paid_amount) as total_amount,
            SUM(mp.paid_for_referrals) as total_referrals,
            AVG(mp.paid_amount) as avg_amount
        FROM monthly_payments mp
        WHERE mp.payment_month = ?
    ");
    $stmt->execute([$month]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $title = "Отчет по выплатам за " . formatMonth($month);
    
    if ($format === 'excel') {
        generateExcelReport($payments, $stats, $title, $month);
    } else {
        generateHTMLReport($payments, $stats, $title, 'monthly', $month);
    }
}

/**
 * Генерация отчета за все время
 */
function generateAllTimeReport($pdo, $format) {
    // Получаем общие данные всех пользователей
    $sort_by = $_GET['sort_by'] ?? 'total_paid';
    $order_sql = match($sort_by) {
        'referrals_earnings' => 'u.total_paid_for_referrals DESC',
        'referrals_count' => 'referral_count DESC',
        'name' => 'u.full_name ASC',
        default => 'u.total_paid_amount DESC'
    };
    
    $stmt = $pdo->query("
        SELECT u.full_name, u.telegram_username, u.is_affiliate,
               u.total_paid_amount, u.total_paid_for_referrals,
               a.full_name as affiliate_name,
               (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count,
               u.created_at
        FROM users u
        LEFT JOIN users a ON u.affiliate_id = a.id
        WHERE u.total_paid_amount > 0 OR u.total_paid_for_referrals > 0
        ORDER BY u.is_affiliate DESC, {$order_sql}
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем разбивку по месяцам если запрошена
    $monthly_breakdown = [];
    if (isset($_GET['include_monthly_breakdown'])) {
        $stmt = $pdo->query("
            SELECT u.full_name, mp.payment_month, mp.paid_amount, mp.paid_for_referrals
            FROM monthly_payments mp
            JOIN users u ON mp.user_id = u.id
            ORDER BY u.full_name, mp.payment_month DESC
        ");
        $breakdown_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($breakdown_data as $row) {
            $monthly_breakdown[$row['full_name']][$row['payment_month']] = [
                'paid_amount' => $row['paid_amount'],
                'paid_for_referrals' => $row['paid_for_referrals']
            ];
        }
    }
    
    // Общая статистика
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN is_affiliate = 1 THEN 1 END) as total_affiliates,
            SUM(total_paid_amount) as total_amount,
            SUM(total_paid_for_referrals) as total_referrals,
            AVG(total_paid_amount) as avg_amount,
            MAX(total_paid_amount) as max_amount,
            MIN(created_at) as first_user_date,
            MAX(created_at) as last_user_date
        FROM users
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $title = "Отчет за все время";
    
    if ($format === 'excel') {
        generateExcelReport($users, $stats, $title, null, $monthly_breakdown);
    } else {
        generateHTMLReport($users, $stats, $title, 'all_time', null, $monthly_breakdown);
    }
}

/**
 * Генерация отчета топ партнёров
 */
function generateTopPerformersReport($pdo, $format) {
    $stmt = $pdo->query("
        SELECT u.full_name, u.telegram_username, u.is_affiliate,
               u.total_paid_amount, u.total_paid_for_referrals,
               (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count,
               u.created_at
        FROM users u
        WHERE u.is_affiliate = 1
        ORDER BY u.total_paid_for_referrals DESC, referral_count DESC
        LIMIT 50
    ");
    $performers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
        'total_performers' => count($performers),
        'total_amount' => array_sum(array_column($performers, 'total_paid_amount')),
        'total_referrals' => array_sum(array_column($performers, 'total_paid_for_referrals'))
    ];
    
    $title = "Топ партнёры";
    
    if ($format === 'excel') {
        generateExcelReport($performers, $stats, $title);
    } else {
        generateHTMLReport($performers, $stats, $title, 'top_performers');
    }
}

/**
 * Генерация HTML отчета
 */
function generateHTMLReport($data, $stats, $title, $type = 'monthly', $month = null, $monthly_breakdown = null) {
    $total_paid = array_sum(array_column($data, isset($data[0]['paid_amount']) ? 'paid_amount' : 'total_paid_amount'));
    $total_paid_referrals = array_sum(array_column($data, isset($data[0]['paid_for_referrals']) ? 'paid_for_referrals' : 'total_paid_for_referrals'));
    
    $month_formatted = $month ? formatMonth($month) : '';
    
    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                margin: 20px; 
                background: #f5f5f5; 
                color: #333; 
            }
            .report-container { 
                max-width: 1200px; 
                margin: 0 auto; 
                background: white; 
                padding: 30px; 
                border-radius: 8px; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            }
            .header { 
                text-align: center; 
                margin-bottom: 30px; 
                border-bottom: 2px solid #2563eb; 
                padding-bottom: 20px; 
            }
            .header h1 { 
                color: #2563eb; 
                margin: 0; 
                font-size: 28px; 
            }
            .print-btn { 
                position: fixed; 
                top: 20px; 
                right: 20px; 
                background: #2563eb; 
                color: white; 
                border: none; 
                padding: 10px 20px; 
                border-radius: 5px; 
                cursor: pointer; 
                font-size: 14px;
                z-index: 1000;
            }
            .print-btn:hover { background: #1d4ed8; }
            .stats-grid { 
                display: grid; 
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
                gap: 20px; 
                margin-bottom: 30px; 
            }
            .stat-card { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 20px; 
                border-radius: 8px; 
                text-align: center; 
            }
            .stat-number { 
                font-size: 24px; 
                font-weight: bold; 
                display: block; 
            }
            .stat-label { 
                font-size: 14px; 
                opacity: 0.9; 
                margin-top: 5px; 
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 30px; 
                background: white; 
                border-radius: 8px; 
                overflow: hidden; 
                box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
            }
            th, td { 
                padding: 12px; 
                text-align: left; 
                border-bottom: 1px solid #e5e7eb; 
            }
            th { 
                background: #f9fafb; 
                font-weight: 600; 
                color: #374151; 
                font-size: 14px; 
            }
            .affiliate-row { background-color: #dbeafe; }
            .user-row { background-color: #f9fafb; }
            .total-row { 
                background: #2563eb; 
                color: white; 
                font-weight: bold; 
            }
            .notes { 
                margin-top: 30px; 
                padding: 20px; 
                background: #f9fafb; 
                border-radius: 8px; 
                border-left: 4px solid #2563eb; 
            }
            .notes h3 { margin-top: 0; color: #2563eb; }
            .notes ul { margin-bottom: 0; }
            .monthly-breakdown { 
                margin-top: 30px; 
                padding: 20px; 
                background: #f8fafc; 
                border-radius: 8px; 
            }
            .breakdown-section { 
                margin-bottom: 20px; 
                padding: 15px; 
                background: white; 
                border-radius: 6px; 
                border: 1px solid #e5e7eb; 
            }
            @media print {
                .print-btn { display: none; }
                body { background: white; }
                .report-container { box-shadow: none; }
            }
        </style>
    </head>
    <body>
        <button class="print-btn" onclick="window.print()">🖨️ Печать / Сохранить PDF</button>
        
        <div class="report-container">
            <div class="header">
                <h1>' . htmlspecialchars($title) . '</h1>
                <p>Сгенерирован: ' . date('d.m.Y H:i') . '</p>
            </div>';

    // Статистика
    if ($stats) {
        echo '<div class="stats-grid">';
        if (isset($stats['total_amount'])) {
            echo '<div class="stat-card">
                <span class="stat-number">' . number_format($stats['total_amount'], 2, '.', ' ') . ' ₽</span>
                <span class="stat-label">Всего выплачено</span>
            </div>';
        }
        if (isset($stats['total_referrals'])) {
            echo '<div class="stat-card">
                <span class="stat-number">' . number_format($stats['total_referrals'], 2, '.', ' ') . ' ₽</span>
                <span class="stat-label">За рефералов</span>
            </div>';
        }
        if (isset($stats['total_affiliates'])) {
            echo '<div class="stat-card">
                <span class="stat-number">' . $stats['total_affiliates'] . '</span>
                <span class="stat-label">Партнеров</span>
            </div>';
        }
        if (isset($stats['total_users'])) {
            echo '<div class="stat-card">
                <span class="stat-number">' . $stats['total_users'] . '</span>
                <span class="stat-label">Пользователей</span>
            </div>';
        }
        echo '</div>';
    }

    // Основная таблица
    if (!empty($data)) {
        echo '<table>
            <thead>
                <tr>
                    <th>№</th>
                    <th>ФИО</th>
                    <th>Telegram</th>
                    <th>Тип</th>
                    <th>Рефералов</th>
                    <th>Выплачено</th>
                    <th>За рефералов</th>
                    <th>Итого</th>';
        if (isset($data[0]['affiliate_name'])) {
            echo '<th>Пригласивший</th>';
        }
        echo '</tr>
            </thead>
            <tbody>';

        $counter = 1;
        foreach ($data as $row) {
            $paid_amount = isset($row['paid_amount']) ? $row['paid_amount'] : $row['total_paid_amount'];
            $paid_referrals = isset($row['paid_for_referrals']) ? $row['paid_for_referrals'] : $row['total_paid_for_referrals'];
            $total_for_user = $paid_amount + $paid_referrals;
            
            $row_class = $row['is_affiliate'] ? 'affiliate-row' : 'user-row';
            
            echo '<tr class="' . $row_class . '">
                <td>' . $counter . '</td>
                <td>' . htmlspecialchars($row['full_name']) . '</td>
                <td>' . htmlspecialchars($row['telegram_username']) . '</td>
                <td>' . ($row['is_affiliate'] ? 'Партнер' : 'Пользователь') . '</td>
                <td>' . ($row['referral_count'] ?? 0) . '</td>
                <td>' . number_format($paid_amount, 2, '.', ' ') . ' ₽</td>
                <td>' . number_format($paid_referrals, 2, '.', ' ') . ' ₽</td>
                <td>' . number_format($total_for_user, 2, '.', ' ') . ' ₽</td>';
            if (isset($row['affiliate_name'])) {
                echo '<td>' . htmlspecialchars($row['affiliate_name'] ?? '-') . '</td>';
            }
            echo '</tr>';
            $counter++;
        }

        echo '<tr class="total-row">
            <td colspan="5"><strong>ИТОГО:</strong></td>
            <td><strong>' . number_format($total_paid, 2, '.', ' ') . ' ₽</strong></td>
            <td><strong>' . number_format($total_paid_referrals, 2, '.', ' ') . ' ₽</strong></td>
            <td><strong>' . number_format($total_paid + $total_paid_referrals, 2, '.', ' ') . ' ₽</strong></td>';
        if (isset($data[0]['affiliate_name'])) {
            echo '<td></td>';
        }
        echo '</tr>
            </tbody>
        </table>';
    } else {
        echo '<div style="text-align: center; padding: 50px; color: #666;">
            <h3>Нет данных за выбранный период</h3>
            <p>Не было произведено выплат</p>
        </div>';
    }

    // Разбивка по месяцам для отчета за все время
    if ($monthly_breakdown && isset($_GET['include_monthly_breakdown'])) {
        echo '<div class="monthly-breakdown">
            <h3>Разбивка по месяцам</h3>';
        
        foreach ($monthly_breakdown as $user_name => $months) {
            echo '<div class="breakdown-section">
                <h4>' . htmlspecialchars($user_name) . '</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Месяц</th>
                            <th>Выплачено</th>
                            <th>За рефералов</th>
                            <th>Итого</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($months as $month => $amounts) {
                $month_total = $amounts['paid_amount'] + $amounts['paid_for_referrals'];
                echo '<tr>
                    <td>' . formatMonth($month) . '</td>
                    <td>' . number_format($amounts['paid_amount'], 2, '.', ' ') . ' ₽</td>
                    <td>' . number_format($amounts['paid_for_referrals'], 2, '.', ' ') . ' ₽</td>
                    <td>' . number_format($month_total, 2, '.', ' ') . ' ₽</td>
                </tr>';
            }
            
            echo '</tbody>
                </table>
            </div>';
        }
        echo '</div>';
    }

    echo '<div class="notes">
            <h3>Примечания:</h3>
            <ul>
                <li>Партнеры выделены голубым цветом</li>
                <li>Пользователи выделены серым цветом</li>';
    if ($type === 'monthly') {
        echo '<li>Данные получены из таблицы месячных выплат</li>';
    } else {
        echo '<li>Данные включают общие суммы за все время</li>';
    }
    echo '     <li>Для сохранения в PDF используйте кнопку печати браузера</li>
            </ul>
        </div>
        
        </div>
    </body>
    </html>';
}

/**
 * Генерация Excel отчета (CSV формат)
 */
function generateExcelReport($data, $stats, $title, $month = null, $monthly_breakdown = null) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . transliterate($title) . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM для корректного отображения UTF-8 в Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Заголовок
    fputcsv($output, [$title], ';');
    fputcsv($output, ['Дата создания: ' . date('d.m.Y H:i')], ';');
    fputcsv($output, [], ';'); // Пустая строка
    
    // Статистика
    if ($stats) {
        fputcsv($output, ['СТАТИСТИКА'], ';');
        foreach ($stats as $key => $value) {
            fputcsv($output, [translateStatKey($key), formatValue($key, $value)], ';');
        }
        fputcsv($output, [], ';'); // Пустая строка
    }
    
    // Данные пользователей
    fputcsv($output, ['ДАННЫЕ ПОЛЬЗОВАТЕЛЕЙ'], ';');
    $headers = ['ФИО', 'Telegram', 'Тип', 'Выплачено', 'За рефералов', 'Кол-во рефералов', 'Итого'];
    if (isset($data[0]['affiliate_name'])) {
        $headers[] = 'Пригласивший';
    }
    fputcsv($output, $headers, ';');
    
    foreach ($data as $row) {
        $paid_amount = isset($row['paid_amount']) ? $row['paid_amount'] : $row['total_paid_amount'];
        $paid_referrals = isset($row['paid_for_referrals']) ? $row['paid_for_referrals'] : $row['total_paid_for_referrals'];
        
        $csv_row = [
            $row['full_name'],
            $row['telegram_username'],
            $row['is_affiliate'] ? 'Партнёр' : 'Пользователь',
            $paid_amount,
            $paid_referrals,
            $row['referral_count'] ?? 0,
            $paid_amount + $paid_referrals
        ];
        if (isset($row['affiliate_name'])) {
            $csv_row[] = $row['affiliate_name'] ?? '-';
        }
        fputcsv($output, $csv_row, ';');
    }
    
    fclose($output);
}

/**
 * Вспомогательные функции
 */
function formatMonth($month) {
    $months = [
        '01' => 'Январь', '02' => 'Февраль', '03' => 'Март', '04' => 'Апрель',
        '05' => 'Май', '06' => 'Июнь', '07' => 'Июль', '08' => 'Август',
        '09' => 'Сентябрь', '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
    ];
    list($year, $month_num) = explode('-', $month);
    return $months[$month_num] . ' ' . $year;
}

function translateStatKey($key) {
    $translations = [
        'total_users' => 'Всего пользователей',
        'total_affiliates' => 'Всего партнёров',
        'total_amount' => 'Общая сумма выплат',
        'total_referrals' => 'Выплачено за рефералов',
        'avg_amount' => 'Средняя выплата',
        'max_amount' => 'Максимальная выплата',
        'first_user_date' => 'Первый пользователь',
        'last_user_date' => 'Последний пользователь',
        'total_performers' => 'Количество партнёров'
    ];
    return $translations[$key] ?? $key;
}

function formatValue($key, $value) {
    if (in_array($key, ['total_amount', 'total_referrals', 'avg_amount', 'max_amount'])) {
        return number_format($value, 2) . ' ₽';
    }
    if (in_array($key, ['first_user_date', 'last_user_date'])) {
        return date('d.m.Y', strtotime($value));
    }
    return $value;
}

function transliterate($string) {
    $transliteration = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Zh',
        'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch', 'Ь' => '', 'Ы' => 'Y', 'Ъ' => '', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        ' ' => '_'
    ];
    return strtr($string, $transliteration);
}
?>