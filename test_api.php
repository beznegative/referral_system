<?php
// Тест API для диагностики ошибок
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Тест API реферальной системы</h1>";

// Тест 1: Подключение к БД
echo "<h2>1. Тест подключения к БД</h2>";
try {
    require_once 'includes/database.php';
    echo "<p style='color: green;'>✓ Подключение к БД успешно</p>";
    
    // Проверяем таблицы
    $tables = ['users', 'referral_earnings', 'referral_settings', 'bookmakers', 'user_bookmakers'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Таблица {$table} существует</p>";
        } else {
            echo "<p style='color: red;'>✗ Таблица {$table} не найдена</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Ошибка подключения к БД: " . $e->getMessage() . "</p>";
}

// Тест 2: Получение партнеров
echo "<h2>2. Тест получения партнеров</h2>";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE is_affiliate = 1");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ Найдено партнеров: " . $result['count'] . "</p>";
    
    // Показываем список партнеров
    $stmt = $pdo->prepare("SELECT id, full_name, telegram_username FROM users WHERE is_affiliate = 1 LIMIT 5");
    $stmt->execute();
    $affiliates = $stmt->fetchAll();
    
    if ($affiliates) {
        echo "<ul>";
        foreach ($affiliates as $affiliate) {
            echo "<li>ID: {$affiliate['id']}, Имя: {$affiliate['full_name']}, Telegram: {$affiliate['telegram_username']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ Партнеров в базе данных нет</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Ошибка получения партнеров: " . $e->getMessage() . "</p>";
}

// Тест 3: Тест API get_affiliates_api.php
echo "<h2>3. Тест API get_affiliates_api.php</h2>";
try {
    // Эмулируем запрос к API
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    include 'get_affiliates_api.php';
    $output = ob_get_clean();
    
    $data = json_decode($output, true);
    if ($data && isset($data['success'])) {
        if ($data['success']) {
            echo "<p style='color: green;'>✓ API работает корректно</p>";
            echo "<p>Партнеров в API: " . count($data['affiliates']) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ API вернул ошибку: " . ($data['error'] ?? 'Неизвестная ошибка') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ API вернул некорректный JSON</p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Ошибка тестирования API: " . $e->getMessage() . "</p>";
}

// Тест 4: Проверка прав на файлы
echo "<h2>4. Тест прав на файлы</h2>";
$files = ['get_affiliates_api.php', 'register_api.php', 'check_user_api.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "<p style='color: green;'>✓ Файл {$file} доступен для чтения</p>";
        } else {
            echo "<p style='color: red;'>✗ Файл {$file} недоступен для чтения</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Файл {$file} не найден</p>";
    }
}

// Тест 5: PHP конфигурация
echo "<h2>5. Тест конфигурации PHP</h2>";
echo "<p>Версия PHP: " . phpversion() . "</p>";
echo "<p>Поддержка JSON: " . (function_exists('json_encode') ? '✓' : '✗') . "</p>";
echo "<p>Поддержка PDO: " . (class_exists('PDO') ? '✓' : '✗') . "</p>";
echo "<p>Поддержка MySQL: " . (extension_loaded('pdo_mysql') ? '✓' : '✗') . "</p>";
echo "<p>Максимальное время выполнения: " . ini_get('max_execution_time') . " сек.</p>";
echo "<p>Максимальный размер POST: " . ini_get('post_max_size') . "</p>";

// Тест 6: Кодировка
echo "<h2>6. Тест кодировки</h2>";
echo "<p>Кодировка скрипта: " . mb_internal_encoding() . "</p>";
echo "<p>Тест русских символов: Привет мир! 🎉</p>";

echo "<hr>";
echo "<p><strong>Дата тестирования:</strong> " . date('Y-m-d H:i:s') . "</p>";
?> 