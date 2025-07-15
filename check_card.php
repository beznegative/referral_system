<?php
require_once 'includes/database.php';

try {
    $stmt = $pdo->prepare("SELECT bank_card FROM users WHERE id = ?");
    $stmt->execute([2]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Зашифрованное значение: " . $result['bank_card'] . "\n";
        echo "Длина: " . strlen($result['bank_card']) . "\n";
        
        // Пробуем base64_decode
        $decoded = base64_decode($result['bank_card']);
        echo "Длина после base64_decode: " . strlen($decoded) . "\n";
        
        // Показываем первые 16 байт (IV)
        echo "IV (первые 16 байт): " . bin2hex(substr($decoded, 0, 16)) . "\n";
    } else {
        echo "Пользователь не найден";
    }
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
} 