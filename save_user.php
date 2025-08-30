<?php
require_once 'includes/database.php';
require_once 'referral_calculator.php';

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?error=' . urlencode('Недопустимый метод запроса'));
    exit;
}

// Проверяем, что данные формы получены
if (empty($_POST)) {
    header('Location: index.php?error=' . urlencode('Данные формы не получены'));
    exit;
}

// Проверяем обязательные поля
if (empty($_POST['full_name']) || empty($_POST['telegram_username'])) {
    header('Location: user_form.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : '') . 
           '&error=' . urlencode('Заполните все обязательные поля'));
    exit;
}

try {
    $pdo->beginTransaction();

    // Получаем и валидируем основные данные
    $id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : null;
    $full_name = trim($_POST['full_name'] ?? '');
    $telegram_username = trim($_POST['telegram_username'] ?? '');
    $telegram_id = !empty($_POST['telegram_id']) ? trim($_POST['telegram_id']) : null;
    $is_affiliate = isset($_POST['is_affiliate']) ? 1 : 0;
    $affiliate_id = !empty($_POST['affiliate_id']) ? intval($_POST['affiliate_id']) : null;
    $monthly_paid_amount = !empty($_POST['monthly_paid_amount']) ? floatval($_POST['monthly_paid_amount']) : 0.00;
    $monthly_paid_for_referrals = !empty($_POST['monthly_paid_for_referrals']) ? floatval($_POST['monthly_paid_for_referrals']) : 0.00;
    $payment_month = !empty($_POST['payment_month_combined']) ? $_POST['payment_month_combined'] : 
                     (!empty($_POST['payment_month']) ? $_POST['payment_month'] : date('Y-m'));
    
    // Дополнительная валидация обязательных полей
    if (empty($full_name)) {
        throw new Exception('Поле "ФИО" обязательно для заполнения');
    }
    if (empty($telegram_username)) {
        throw new Exception('Поле "Имя пользователя Telegram" обязательно для заполнения');
    }

    // Автоматический расчет общих сумм на основе данных из monthly_payments + текущий месяц
    // Поля total_paid_amount и total_paid_for_referrals всегда рассчитываются автоматически
    $total_paid_amount = $monthly_paid_amount;
    $total_paid_for_referrals = $monthly_paid_for_referrals;
    
    if ($id) {
        // Для существующего пользователя: суммируем все месяцы кроме текущего + текущий месяц
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(paid_amount), 0) as archived_amount,
                COALESCE(SUM(paid_for_referrals), 0) as archived_referrals
            FROM monthly_payments 
            WHERE user_id = ? AND payment_month != ?
        ");
        $stmt->execute([$id, $payment_month]);
        $archived = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_paid_amount = floatval($archived['archived_amount']) + $monthly_paid_amount;
        $total_paid_for_referrals = floatval($archived['archived_referrals']) + $monthly_paid_for_referrals;
    }



    if ($id) {
        // Получаем старые значения для сравнения
        $stmt = $pdo->prepare("SELECT total_paid_amount, payment_month FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $old_total_paid_amount = $old_data['total_paid_amount'] ?? 0;
        $old_payment_month = $old_data['payment_month'] ?? null;
        
        // Всегда сохраняем/обновляем данные в monthly_payments для текущего месяца
        $stmt = $pdo->prepare("
            INSERT INTO monthly_payments (user_id, payment_month, paid_amount, paid_for_referrals) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            paid_amount = VALUES(paid_amount),
            paid_for_referrals = VALUES(paid_for_referrals)
        ");
        $stmt->execute([
            $id, 
            $payment_month, 
            $monthly_paid_amount,
            $monthly_paid_for_referrals
        ]);
        
        // Обновляем месячные выплаты за рефералов для всех партнёров за этот месяц
        updateMonthlyAffiliatePayments($pdo, $payment_month);
        
        // Обновление существующего пользователя
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, 
                telegram_username = ?, 
                telegram_id = ?, 
                is_affiliate = ?,
                affiliate_id = ?,
                total_paid_amount = ?,
                total_paid_for_referrals = ?,
                monthly_paid_amount = ?,
                monthly_paid_for_referrals = ?,
                payment_month = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $full_name,
            $telegram_username,
            $telegram_id,
            $is_affiliate,
            $affiliate_id,
            $total_paid_amount,
            $total_paid_for_referrals,
            $monthly_paid_amount,
            $monthly_paid_for_referrals,
            $payment_month,
            $id
        ]);
        
        // Если total_paid_amount изменился, пересчитываем выплаты партнерам
        if ($old_total_paid_amount != $total_paid_amount) {
            updateAffiliatePayments($pdo, $id);
        }
    } else {
        // Создание нового пользователя
        $stmt = $pdo->prepare("
            INSERT INTO users (
                full_name, telegram_username, telegram_id, 
                is_affiliate, affiliate_id,
                total_paid_amount, total_paid_for_referrals,
                monthly_paid_amount, monthly_paid_for_referrals, payment_month
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $full_name,
            $telegram_username,
            $telegram_id,
            $is_affiliate,
            $affiliate_id,
            $total_paid_amount,
            $total_paid_for_referrals,
            $monthly_paid_amount,
            $monthly_paid_for_referrals,
            $payment_month
        ]);

        $id = $pdo->lastInsertId();
        
        // Сохраняем данные в monthly_payments для нового пользователя
        if ($monthly_paid_amount > 0 || $monthly_paid_for_referrals > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO monthly_payments (user_id, payment_month, paid_amount, paid_for_referrals) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $id, 
                $payment_month, 
                $monthly_paid_amount,
                $monthly_paid_for_referrals
            ]);
            
            // Обновляем месячные выплаты за рефералов для всех партнёров за этот месяц
            updateMonthlyAffiliatePayments($pdo, $payment_month);
        }
        
        // Если новый пользователь создан с total_paid_amount > 0, пересчитываем выплаты партнерам
        if ($total_paid_amount > 0) {
            updateAffiliatePayments($pdo, $id);
        }
    }

    // Обновляем связи с букмекерскими конторами
    if ($id) {
        // Удаляем старые связи
        $stmt = $pdo->prepare("DELETE FROM user_bookmakers WHERE user_id = ?");
        $stmt->execute([$id]);

        // Добавляем новые связи
        if (isset($_POST['bookmakers']) && is_array($_POST['bookmakers'])) {
            $stmt = $pdo->prepare("INSERT INTO user_bookmakers (user_id, bookmaker_id) VALUES (?, ?)");
            foreach ($_POST['bookmakers'] as $bookmaker_id) {
                $stmt->execute([$id, $bookmaker_id]);
            }
        }
    }

    // Обновляем количество рефералов для всех партнёров
    require_once 'update_referral_counts.php';
    updateReferralCounts($pdo);

    $pdo->commit();
    
    // Успешное сохранение - перенаправляем с сообщением об успехе
    $redirect_url = isset($id) && $id ? 'user.php?id=' . $id . '&success=1' : 'index.php?success=1';
    
    // Используем несколько методов перенаправления для надежности
    header('Location: ' . $redirect_url);
    
    // Запасной вариант через HTML мета-тег
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url) . '">
        <script>window.location.href = "' . htmlspecialchars($redirect_url) . '";</script>
    </head>
    <body>
        <p>Данные сохранены. Если страница не перенаправилась автоматически, <a href="' . htmlspecialchars($redirect_url) . '">нажмите здесь</a>.</p>
    </body>
    </html>';
    exit;
    
} catch (Exception $e) {
    // Проверяем, есть ли активная транзакция перед откатом
    if ($pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Exception $rollbackException) {
            // Если откат не удался, логируем ошибку
            error_log('Ошибка при откате транзакции: ' . $rollbackException->getMessage());
        }
    }
    
    // Логируем ошибку для отладки
    error_log('Ошибка в save_user.php: ' . $e->getMessage());
    
    // Перенаправляем обратно на форму с сообщением об ошибке
    $redirect_url = 'user_form.php';
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $redirect_url .= '?id=' . $_POST['id'];
    }
    $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . 
                     'error=' . urlencode('Ошибка при сохранении: ' . $e->getMessage());
    
    header('Location: ' . $redirect_url);
    exit;
} 