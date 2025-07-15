<?php
// Проверка настроек Telegram бота
require_once 'includes/database.php';
require_once 'telegram_api.php';

echo "<h2>Диагностика Telegram бота</h2>";

// 1. Проверка таблицы api_tokens
echo "<h3>1. Проверка таблицы api_tokens</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'api_tokens'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Таблица api_tokens существует<br>";
        
        // Проверка содержимого таблицы
        $stmt = $pdo->query("SELECT * FROM api_tokens");
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($tokens)) {
            echo "❌ Таблица api_tokens пуста<br>";
            
            // Создаем таблицу и добавляем токен
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS api_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    token_name VARCHAR(100) NOT NULL UNIQUE,
                    token_value TEXT NOT NULL,
                    description VARCHAR(255),
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            $pdo->exec("
                INSERT INTO api_tokens (token_name, token_value, description) VALUES 
                ('telegram_bot_token', '7918934050:AAG-z4UDjnkDqhdB7_5PbXYB-fBEs9UiHxM', 'Токен для Telegram бота')
                ON DUPLICATE KEY UPDATE token_value = VALUES(token_value)
            ");
            
            echo "✅ Токен добавлен в таблицу<br>";
        } else {
            echo "✅ Найдены токены:<br>";
            foreach ($tokens as $token) {
                echo "- {$token['token_name']}: " . substr($token['token_value'], 0, 10) . "...<br>";
            }
        }
    } else {
        echo "❌ Таблица api_tokens не существует<br>";
        
        // Создаем таблицу и добавляем токен
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS api_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token_name VARCHAR(100) NOT NULL UNIQUE,
                token_value TEXT NOT NULL,
                description VARCHAR(255),
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $pdo->exec("
            INSERT INTO api_tokens (token_name, token_value, description) VALUES 
            ('telegram_bot_token', '7918934050:AAG-z4UDjnkDqhdB7_5PbXYB-fBEs9UiHxM', 'Токен для Telegram бота')
        ");
        
        echo "✅ Таблица создана и токен добавлен<br>";
    }
} catch (Exception $e) {
    echo "❌ Ошибка при проверке таблицы: " . $e->getMessage() . "<br>";
}

// 2. Проверка подключения к Telegram API
echo "<h3>2. Проверка подключения к Telegram API</h3>";
try {
    $telegram = new TelegramAPI($pdo);
    $result = $telegram->getMe();
    
    if ($result['ok']) {
        $bot = $result['result'];
        echo "✅ Бот подключен успешно<br>";
        echo "- Имя: {$bot['first_name']}<br>";
        echo "- Username: @{$bot['username']}<br>";
        echo "- ID: {$bot['id']}<br>";
    } else {
        echo "❌ Ошибка подключения к боту: " . ($result['error'] ?? 'Неизвестная ошибка') . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Исключение при подключении к боту: " . $e->getMessage() . "<br>";
}

// 3. Проверка партнеров с telegram_id
echo "<h3>3. Проверка партнеров с telegram_id</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_affiliates FROM users WHERE is_affiliate = 1");
    $totalAffiliates = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as with_telegram FROM users WHERE telegram_id IS NOT NULL AND telegram_id != '' AND is_affiliate = 1");
    $withTelegram = $stmt->fetchColumn();
    
    echo "✅ Всего пользователей: {$total}<br>";
    echo "✅ Всего партнеров: {$totalAffiliates}<br>";
    echo "✅ Партнеров с telegram_id: {$withTelegram}<br>";
    
    if ($withTelegram > 0) {
        $stmt = $pdo->query("SELECT full_name, telegram_id FROM users WHERE telegram_id IS NOT NULL AND telegram_id != '' AND is_affiliate = 1 LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<strong>Примеры партнеров:</strong><br>";
        foreach ($users as $user) {
            echo "- {$user['full_name']}: {$user['telegram_id']}<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Ошибка при проверке партнеров: " . $e->getMessage() . "<br>";
}

// 4. Тест отправки сообщения одному партнеру
echo "<h3>4. Тест отправки сообщения</h3>";
try {
    $stmt = $pdo->query("SELECT telegram_id, full_name FROM users WHERE telegram_id IS NOT NULL AND telegram_id != '' AND is_affiliate = 1 LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $telegram = new TelegramAPI($pdo);
        $result = $telegram->sendMessage($user['telegram_id'], "Тестовое сообщение от бота реферальной системы!");
        
        if ($result['ok']) {
            echo "✅ Тестовое сообщение отправлено партнеру {$user['full_name']}<br>";
        } else {
            echo "❌ Ошибка отправки сообщения партнеру {$user['full_name']}: " . ($result['error'] ?? 'Неизвестная ошибка') . "<br>";
            echo "Debug info: " . print_r($result, true) . "<br>";
        }
    } else {
        echo "❌ Нет партнеров с telegram_id для тестирования<br>";
    }
} catch (Exception $e) {
    echo "❌ Исключение при тестировании отправки: " . $e->getMessage() . "<br>";
}

// 5. Проверка cURL
echo "<h3>5. Проверка cURL</h3>";
if (function_exists('curl_init')) {
    echo "✅ cURL доступен<br>";
    
    // Проверка SSL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot7918934050:AAG-z4UDjnkDqhdB7_5PbXYB-fBEs9UiHxM/getMe");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL ошибка: {$error}<br>";
    } else {
        echo "✅ HTTP код: {$httpCode}<br>";
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && $data['ok']) {
                echo "✅ Прямой запрос к Telegram API работает<br>";
            } else {
                echo "❌ Ошибка в ответе API: " . ($data['description'] ?? 'Неизвестная ошибка') . "<br>";
            }
        }
    }
} else {
    echo "❌ cURL не доступен<br>";
}

echo "<hr>";
echo "<a href='settings.php'>Вернуться к настройкам</a>";
?> 