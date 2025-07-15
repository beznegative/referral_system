<?php
require_once 'includes/database.php';

// Ключ шифрования
define('ENCRYPTION_KEY', 'your-secret-key-123');

// Функция для шифрования данных
function encryptData($data) {
    if (empty($data)) {
        return '';
    }
    
    $method = "AES-256-CBC";
    $key = substr(hash('sha256', ENCRYPTION_KEY, true), 0, 32);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    
    if ($encrypted === false) {
        return '';
    }
    
    return base64_encode($iv . base64_decode($encrypted));
}

try {
    // Получаем все записи с банковскими картами
    $stmt = $pdo->query("SELECT id, bank_card FROM users WHERE bank_card IS NOT NULL AND bank_card != ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        // Перешифровываем карту
        $encrypted_card = encryptData($user['bank_card']);
        
        // Обновляем запись
        $update = $pdo->prepare("UPDATE users SET bank_card = ? WHERE id = ?");
        $update->execute([$encrypted_card, $user['id']]);
        
        echo "Обновлена карта для пользователя ID: " . $user['id'] . "<br>";
    }

    echo "Все банковские карты успешно перешифрованы!";
} catch (Exception $e) {
    die('Ошибка: ' . htmlspecialchars($e->getMessage()));
} 