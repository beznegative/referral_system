<?php
/**
 * Конфигурация системы капчи
 */

// Тип капчи: 'simple' или 'recaptcha'
define('CAPTCHA_TYPE', 'simple'); // Можно изменить на 'simple' для простой капчи

// Настройки Google reCAPTCHA v2
// Ваши реальные ключи от Google reCAPTCHA
define('RECAPTCHA_SITE_KEY', '6LdGh5MrAAAAANG9YxCxFUfyIUI1jJhOFvnOsD1E'); // Ваш Site Key
define('RECAPTCHA_SECRET_KEY', '6LdGh5MrAAAAAEgXAVNJfAObVqbCqeEYzV4VwFTv'); // Ваш Secret Key

// Настройки простой капчи
define('SIMPLE_CAPTCHA_ENABLED', true);

// Время действия капчи (в секундах)
define('CAPTCHA_EXPIRES_TIME', 30 * 60); // 30 минут

// Настройки логирования
define('CAPTCHA_LOGGING_ENABLED', true);
define('CAPTCHA_LOG_DIR', 'logs');

// Настройки безопасности
define('CAPTCHA_CHECK_IP', true); // Проверять IP адрес
define('CAPTCHA_MAX_ATTEMPTS', 5); // Максимум попыток за час

/**
 * Получить конфигурацию капчи
 */
function getCaptchaConfig() {
    return [
        'type' => CAPTCHA_TYPE,
        'recaptcha' => [
            'site_key' => RECAPTCHA_SITE_KEY,
            'secret_key' => RECAPTCHA_SECRET_KEY
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
 * Проверить, включена ли reCAPTCHA
 */
function isRecaptchaEnabled() {
    return CAPTCHA_TYPE === 'recaptcha' && !empty(RECAPTCHA_SITE_KEY) && !empty(RECAPTCHA_SECRET_KEY);
}

/**
 * Получить Site Key для reCAPTCHA
 */
function getRecaptchaSiteKey() {
    return RECAPTCHA_SITE_KEY;
}

/**
 * Получить Secret Key для reCAPTCHA
 */
function getRecaptchaSecretKey() {
    return RECAPTCHA_SECRET_KEY;
}
?> 