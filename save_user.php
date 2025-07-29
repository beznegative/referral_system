<?php
require_once 'includes/database.php';
require_once 'referral_calculator.php';

// Ключ шифрования (должен быть таким же, как в user_form.php)
define('ENCRYPTION_KEY', 'your-secret-key-123');

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

try {
    $pdo->beginTransaction();

    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $is_affiliate = isset($_POST['is_affiliate']) ? 1 : 0;
    $affiliate_id = !empty($_POST['affiliate_id']) ? $_POST['affiliate_id'] : null;
    $monthly_paid_amount = !empty($_POST['monthly_paid_amount']) ? floatval($_POST['monthly_paid_amount']) : 0.00;
    $monthly_paid_for_referrals = !empty($_POST['monthly_paid_for_referrals']) ? floatval($_POST['monthly_paid_for_referrals']) : 0.00;
    $payment_month = !empty($_POST['payment_month']) ? $_POST['payment_month'] : date('Y-m');
    $telegram_id = !empty($_POST['telegram_id']) ? $_POST['telegram_id'] : null;

    // Получаем введенные общие суммы (если есть)
    $input_total_paid_amount = !empty($_POST['total_paid_amount']) ? floatval($_POST['total_paid_amount']) : null;
    $input_total_paid_for_referrals = !empty($_POST['total_paid_for_referrals']) ? floatval($_POST['total_paid_for_referrals']) : null;
    
    // Автоматический расчет общих сумм на основе месячных данных и архивных данных
    $total_paid_amount = 0.00;
    $total_paid_for_referrals = 0.00;
    
    if ($id) {
        // Для существующего пользователя: суммируем архивные данные + текущий месяц
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
        
        // Если пользователь вручную ввел общие суммы, используем их (для случаев корректировки)
        if ($input_total_paid_amount !== null) {
            $total_paid_amount = $input_total_paid_amount;
        }
        if ($input_total_paid_for_referrals !== null) {
            $total_paid_for_referrals = $input_total_paid_for_referrals;
        }
    } else {
        // Для нового пользователя: используем месячные данные или введенные общие суммы
        $total_paid_amount = $input_total_paid_amount ?? $monthly_paid_amount;
        $total_paid_for_referrals = $input_total_paid_for_referrals ?? $monthly_paid_for_referrals;
    }

    // Шифруем банковскую карту
    $encrypted_bank_card = encryptData($_POST['bank_card']);

    if ($id) {
        // Получаем старые значения для сравнения
        $stmt = $pdo->prepare("SELECT total_paid_amount, payment_month FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $old_total_paid_amount = $old_data['total_paid_amount'] ?? 0;
        $old_payment_month = $old_data['payment_month'] ?? null;
        
        // Если месяц изменился, сохраняем предыдущие месячные данные в историю
        if ($old_payment_month && $old_payment_month !== $payment_month) {
            $stmt = $pdo->prepare("
                INSERT INTO monthly_payments (user_id, payment_month, paid_amount, paid_for_referrals) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                paid_amount = VALUES(paid_amount),
                paid_for_referrals = VALUES(paid_for_referrals)
            ");
            $stmt->execute([$id, $old_payment_month, $monthly_paid_amount, $monthly_paid_for_referrals]);
        }
        
        // Обновление существующего пользователя
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, 
                bank_card = ?, 
                telegram_username = ?, 
                telegram_id = ?, 
                phone_number = ?, 
                birth_date = ?,
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
            $_POST['full_name'],
            $encrypted_bank_card,
            $_POST['telegram_username'],
            $telegram_id,
            $_POST['phone_number'],
            $_POST['birth_date'],
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
                full_name, bank_card, telegram_username, telegram_id, 
                phone_number, birth_date, is_affiliate, affiliate_id,
                total_paid_amount, total_paid_for_referrals,
                monthly_paid_amount, monthly_paid_for_referrals, payment_month
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['full_name'],
            $encrypted_bank_card,
            $_POST['telegram_username'],
            $telegram_id,
            $_POST['phone_number'],
            $_POST['birth_date'],
            $is_affiliate,
            $affiliate_id,
            $total_paid_amount,
            $total_paid_for_referrals,
            $monthly_paid_amount,
            $monthly_paid_for_referrals,
            $payment_month
        ]);

        $id = $pdo->lastInsertId();
        
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
    header('Location: index.php');
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
    
    die('Ошибка при сохранении пользователя: ' . htmlspecialchars($e->getMessage()));
} 