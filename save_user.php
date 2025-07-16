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
    $paid_amount = !empty($_POST['paid_amount']) ? $_POST['paid_amount'] : 0.00;
    $paid_for_referrals = !empty($_POST['paid_for_referrals']) ? $_POST['paid_for_referrals'] : 0.00;
    $telegram_id = !empty($_POST['telegram_id']) ? $_POST['telegram_id'] : null;

    // Шифруем банковскую карту
    $encrypted_bank_card = encryptData($_POST['bank_card']);

    if ($id) {
        // Получаем старое значение paid_amount для сравнения
        $stmt = $pdo->prepare("SELECT paid_amount FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $old_paid_amount = $stmt->fetchColumn();
        
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
                paid_amount = ?,
                paid_for_referrals = ?
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
            $paid_amount,
            $paid_for_referrals,
            $id
        ]);
        
        // Если paid_amount изменился, пересчитываем выплаты партнерам
        if ($old_paid_amount != $paid_amount) {
            updateAffiliatePayments($pdo, $id);
        }
    } else {
        // Создание нового пользователя
        $stmt = $pdo->prepare("
            INSERT INTO users (
                full_name, bank_card, telegram_username, telegram_id, 
                phone_number, birth_date, is_affiliate, affiliate_id,
                paid_amount, paid_for_referrals
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $paid_amount,
            $paid_for_referrals
        ]);

        $id = $pdo->lastInsertId();
        
        // Если новый пользователь создан с paid_amount > 0, пересчитываем выплаты партнерам
        if ($paid_amount > 0) {
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