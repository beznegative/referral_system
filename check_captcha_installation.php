#!/usr/bin/env php
<?php
/**
 * Скрипт проверки установки системы капчи
 * 
 * Использование:
 * php check_captcha_installation.php
 * или откройте в браузере
 */

// Определяем, запущен ли скрипт из командной строки
$isCLI = (php_sapi_name() === 'cli');

if (!$isCLI) {
    // Веб-интерфейс
    echo "<!DOCTYPE html>\n<html><head><title>Проверка установки капчи</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:40px;} .ok{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";
    echo "</head><body><h1>🛡️ Проверка установки системы капчи</h1>";
}

function output($message, $type = 'info') {
    global $isCLI;
    
    if ($isCLI) {
        $prefix = '';
        switch ($type) {
            case 'ok': $prefix = '✅ '; break;
            case 'error': $prefix = '❌ '; break;
            case 'warning': $prefix = '⚠️  '; break;
            default: $prefix = 'ℹ️  '; break;
        }
        echo $prefix . $message . "\n";
    } else {
        $class = $type === 'ok' ? 'ok' : ($type === 'error' ? 'error' : ($type === 'warning' ? 'warning' : ''));
        echo "<p class='$class'>$message</p>\n";
    }
}

function checkFileExists($file, $description) {
    if (file_exists($file)) {
        output("$description найден: $file", 'ok');
        return true;
    } else {
        output("$description НЕ найден: $file", 'error');
        return false;
    }
}

output("Начинаем проверку системы капчи...", 'info');
output("Время проверки: " . date('Y-m-d H:i:s'), 'info');

// 1. Проверка основных файлов
output("\n=== ПРОВЕРКА ФАЙЛОВ ===", 'info');

$requiredFiles = [
    'captcha.php' => 'Страница капчи',
    'captcha_config.php' => 'Конфигурация капчи',
    'verify_captcha.php' => 'Обработчик верификации',
    'check_captcha.php' => 'Функции проверки',
    'test_captcha_system.php' => 'Тестовая панель',
    'clear_captcha_session.php' => 'Очистка сессии'
];

$missingFiles = 0;
foreach ($requiredFiles as $file => $description) {
    if (!checkFileExists($file, $description)) {
        $missingFiles++;
    }
}

// 2. Проверка конфигурации
output("\n=== ПРОВЕРКА КОНФИГУРАЦИИ ===", 'info');

if (file_exists('captcha_config.php')) {
    require_once 'captcha_config.php';
    
    // Проверяем, доступны ли функции конфигурации
    if (function_exists('getCaptchaConfig')) {
        $config = getCaptchaConfig();
        output("Тип капчи: " . $config['type'], 'info');
        
        if ($config['type'] === 'recaptcha') {
            $isRecaptchaEnabled = function_exists('isRecaptchaEnabled') ? isRecaptchaEnabled() : false;
            
            if ($isRecaptchaEnabled) {
                output("Google reCAPTCHA настроена корректно", 'ok');
                
                $siteKey = function_exists('getRecaptchaSiteKey') ? getRecaptchaSiteKey() : 'не найден';
                if ($siteKey !== '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI') {
                    output("Site Key настроен (не тестовый)", 'ok');
                } else {
                    output("ВНИМАНИЕ: Используется тестовый Site Key", 'warning');
                }
            } else {
                output("Google reCAPTCHA НЕ настроена", 'error');
            }
        } else {
            output("Используется простая капча", 'info');
        }
        
        output("Время действия: " . ($config['expires_time'] / 60) . " минут", 'info');
        output("Проверка IP: " . ($config['check_ip'] ? 'включена' : 'отключена'), 'info');
        output("Логирование: " . ($config['logging'] ? 'включено' : 'отключено'), 'info');
        
    } else {
        output("Функции конфигурации не найдены", 'error');
    }
} else {
    output("Файл конфигурации не найден", 'error');
}

// 3. Проверка системных требований
output("\n=== СИСТЕМНЫЕ ТРЕБОВАНИЯ ===", 'info');

// PHP версия
$phpVersion = phpversion();
output("PHP версия: $phpVersion", $phpVersion >= '7.0' ? 'ok' : 'warning');

// Проверка сессий
if (session_status() !== PHP_SESSION_DISABLED) {
    output("PHP сессии поддерживаются", 'ok');
} else {
    output("PHP сессии ОТКЛЮЧЕНЫ", 'error');
}

// Проверка cURL
if (extension_loaded('curl')) {
    output("cURL расширение установлено", 'ok');
} else {
    output("cURL расширение НЕ установлено (нужно для reCAPTCHA)", 'error');
}

// Проверка JSON
if (extension_loaded('json')) {
    output("JSON расширение установлено", 'ok');
} else {
    output("JSON расширение НЕ установлено", 'error');
}

// 4. Проверка папки логов
output("\n=== ПРОВЕРКА ЛОГОВ ===", 'info');

$logsDir = 'logs';
if (file_exists($logsDir)) {
    if (is_writable($logsDir)) {
        output("Папка логов существует и доступна для записи", 'ok');
        
        // Проверяем наличие файлов логов
        $logFiles = ['captcha_verifications.log', 'captcha_errors.log', 'captcha_actions.log'];
        foreach ($logFiles as $logFile) {
            if (file_exists("$logsDir/$logFile")) {
                $size = filesize("$logsDir/$logFile");
                output("Лог $logFile: " . number_format($size) . " байт", 'info');
            }
        }
    } else {
        output("Папка логов существует, но НЕ доступна для записи", 'error');
    }
} else {
    output("Папка логов не существует, будет создана автоматически", 'warning');
}

// 5. Проверка интеграции с основными файлами
output("\n=== ПРОВЕРКА ИНТЕГРАЦИИ ===", 'info');

$protectedFiles = ['miniapp.php', 'test_miniapp.php'];
foreach ($protectedFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'check_captcha.php') !== false) {
            output("$file интегрирован с системой капчи", 'ok');
        } else {
            output("$file НЕ интегрирован с системой капчи", 'warning');
        }
    }
}

// 6. Тест создания сессии
output("\n=== ТЕСТ СЕССИИ ===", 'info');

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['captcha_test'] = time();
    output("Сессия создана успешно", 'ok');
    unset($_SESSION['captcha_test']);
} catch (Exception $e) {
    output("Ошибка создания сессии: " . $e->getMessage(), 'error');
}

// 7. Проверка доступности Google (для reCAPTCHA)
if (isset($config) && $config['type'] === 'recaptcha') {
    output("\n=== ПРОВЕРКА ДОСТУПНОСТИ GOOGLE ===", 'info');
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 405) { // 405 Method Not Allowed is ok for HEAD request
            output("Google reCAPTCHA API доступен", 'ok');
        } else {
            output("Google reCAPTCHA API недоступен (HTTP $httpCode)", 'error');
        }
    }
}

// ИТОГОВЫЙ ОТЧЕТ
output("\n=== ИТОГОВЫЙ ОТЧЕТ ===", 'info');

if ($missingFiles === 0) {
    output("✅ Все файлы системы капчи найдены", 'ok');
} else {
    output("❌ Отсутствует файлов: $missingFiles", 'error');
}

if (isset($config)) {
    if ($config['type'] === 'recaptcha') {
        $recaptchaStatus = (function_exists('isRecaptchaEnabled') && isRecaptchaEnabled()) ? 'настроена' : 'НЕ настроена';
        output("reCAPTCHA: $recaptchaStatus", $recaptchaStatus === 'настроена' ? 'ok' : 'error');
    }
}

output("\n=== РЕКОМЕНДАЦИИ ===", 'info');

if ($missingFiles > 0) {
    output("• Убедитесь, что все файлы системы капчи загружены на сервер", 'warning');
}

if (isset($config) && $config['type'] === 'recaptcha' && (!function_exists('isRecaptchaEnabled') || !isRecaptchaEnabled())) {
    output("• Настройте ключи reCAPTCHA в captcha_config.php", 'warning');
    output("• Получите ключи на https://www.google.com/recaptcha/admin/create", 'info');
}

if (!extension_loaded('curl')) {
    output("• Установите cURL расширение для PHP", 'warning');
}

if (!file_exists('logs') || !is_writable('logs')) {
    output("• Создайте папку logs с правами на запись (chmod 755)", 'warning');
}

output("\nДля тестирования системы откройте: test_captcha_system.php", 'info');
output("Проверка завершена!", 'info');

if (!$isCLI) {
    echo "</body></html>";
}
?> 