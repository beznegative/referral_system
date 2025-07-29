<?php
/**
 * Продакшен конфигурация системы капчи
 * 
 * Инструкции:
 * 1. Скопируйте этот файл как captcha_config.local.php
 * 2. Обновите ключи reCAPTCHA на ваши реальные
 * 3. Настройте остальные параметры под ваши требования
 * 4. Добавьте captcha_config.local.php в .gitignore
 */

// ===========================================
// ОСНОВНЫЕ НАСТРОЙКИ КАПЧИ
// ===========================================

// Тип капчи: 'simple' или 'recaptcha'
define('CAPTCHA_TYPE', 'recaptcha');

// ===========================================
// НАСТРОЙКИ Google reCAPTCHA v2
// ===========================================

// ВАЖНО: Получите ваши ключи на https://www.google.com/recaptcha/admin/create
// Замените эти тестовые ключи на ваши реальные!
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'); // ЗАМЕНИТЕ НА ВАШ
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'); // ЗАМЕНИТЕ НА ВАШ

// ===========================================
// НАСТРОЙКИ БЕЗОПАСНОСТИ
// ===========================================

// Время действия капчи в секундах
define('CAPTCHA_EXPIRES_TIME', 30 * 60); // 30 минут (рекомендуется)

// Проверять IP адрес пользователя
define('CAPTCHA_CHECK_IP', true); // true для продакшена

// Максимальное количество попыток за час с одного IP
define('CAPTCHA_MAX_ATTEMPTS', 10); // Увеличено для продакшена

// ===========================================
// НАСТРОЙКИ ЛОГИРОВАНИЯ
// ===========================================

// Включить логирование
define('CAPTCHA_LOGGING_ENABLED', true);

// Папка для логов
define('CAPTCHA_LOG_DIR', 'logs');

// ===========================================
// ДОПОЛНИТЕЛЬНЫЕ НАСТРОЙКИ
// ===========================================

// Настройки простой капчи (резерв)
define('SIMPLE_CAPTCHA_ENABLED', true);

// Дополнительные домены для reCAPTCHA (если нужно)
// Убедитесь, что они добавлены в Google reCAPTCHA Console
$ALLOWED_DOMAINS = [
    'yourdomain.com',
    'www.yourdomain.com',
    // 'subdomain.yourdomain.com', // если есть поддомены
];

// ===========================================
// ФУНКЦИИ КОНФИГУРАЦИИ
// ===========================================

/**
 * Получить полную конфигурацию капчи
 */
function getCaptchaConfig() {
    global $ALLOWED_DOMAINS;
    
    return [
        'type' => CAPTCHA_TYPE,
        'recaptcha' => [
            'site_key' => RECAPTCHA_SITE_KEY,
            'secret_key' => RECAPTCHA_SECRET_KEY,
            'allowed_domains' => $ALLOWED_DOMAINS ?? []
        ],
        'simple' => [
            'enabled' => SIMPLE_CAPTCHA_ENABLED
        ],
        'expires_time' => CAPTCHA_EXPIRES_TIME,
        'logging' => CAPTCHA_LOGGING_ENABLED,
        'log_dir' => CAPTCHA_LOG_DIR,
        'check_ip' => CAPTCHA_CHECK_IP,
        'max_attempts' => CAPTCHA_MAX_ATTEMPTS
    ];
}

/**
 * Проверить, правильно ли настроена reCAPTCHA
 */
function isRecaptchaEnabled() {
    return CAPTCHA_TYPE === 'recaptcha' && 
           !empty(RECAPTCHA_SITE_KEY) && 
           !empty(RECAPTCHA_SECRET_KEY) &&
           RECAPTCHA_SITE_KEY !== '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'; // Не тестовый ключ
}

/**
 * Получить Site Key для reCAPTCHA
 */
function getRecaptchaSiteKey() {
    if (!isRecaptchaEnabled()) {
        error_log('CAPTCHA WARNING: reCAPTCHA Site Key не настроен или используется тестовый ключ');
    }
    return RECAPTCHA_SITE_KEY;
}

/**
 * Получить Secret Key для reCAPTCHA
 */
function getRecaptchaSecretKey() {
    if (!isRecaptchaEnabled()) {
        error_log('CAPTCHA WARNING: reCAPTCHA Secret Key не настроен или используется тестовый ключ');
    }
    return RECAPTCHA_SECRET_KEY;
}

/**
 * Проверить системные требования
 */
function checkCaptchaRequirements() {
    $requirements = [
        'curl_available' => extension_loaded('curl'),
        'sessions_enabled' => session_status() !== PHP_SESSION_DISABLED,
        'logs_writable' => is_writable(CAPTCHA_LOG_DIR) || is_writable(dirname(__FILE__)),
        'recaptcha_configured' => isRecaptchaEnabled()
    ];
    
    return $requirements;
}

// ===========================================
// ПРОВЕРКА КОНФИГУРАЦИИ ПРИ ЗАГРУЗКЕ
// ===========================================

// Проверяем критичные требования
if (CAPTCHA_TYPE === 'recaptcha') {
    if (!extension_loaded('curl')) {
        error_log('CAPTCHA ERROR: cURL расширение не установлено. reCAPTCHA не будет работать.');
    }
    
    if (RECAPTCHA_SITE_KEY === '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI') {
        error_log('CAPTCHA WARNING: Используется тестовый Site Key. Замените на продакшен ключ.');
    }
    
    if (RECAPTCHA_SECRET_KEY === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe') {
        error_log('CAPTCHA WARNING: Используется тестовый Secret Key. Замените на продакшен ключ.');
    }
}

// Создаем папку для логов если её нет
if (CAPTCHA_LOGGING_ENABLED && !file_exists(CAPTCHA_LOG_DIR)) {
    if (!mkdir(CAPTCHA_LOG_DIR, 0755, true)) {
        error_log('CAPTCHA ERROR: Не удалось создать папку для логов: ' . CAPTCHA_LOG_DIR);
    }
}

// ===========================================
// ДОПОЛНИТЕЛЬНЫЕ НАСТРОЙКИ ДЛЯ ПРОДАКШЕНА
// ===========================================

// Настройки для высоконагруженных сайтов
if (defined('HIGH_LOAD_MODE') && HIGH_LOAD_MODE) {
    // Увеличиваем лимиты для высокой нагрузки
    define('CAPTCHA_MAX_ATTEMPTS', 20);
    define('CAPTCHA_EXPIRES_TIME', 60 * 60); // 1 час
}

// Настройки для разработки
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
    // Более мягкие ограничения для разработки
    define('CAPTCHA_MAX_ATTEMPTS', 100);
    define('CAPTCHA_CHECK_IP', false);
}

?> 