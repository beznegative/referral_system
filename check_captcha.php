<?php
/**
 * Функции для проверки капчи
 */

require_once 'captcha_config.php';

function checkCaptchaStatus() {
    // Начинаем сессию если она еще не начата
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Проверяем, была ли пройдена капча
    if (!isset($_SESSION['captcha_verified']) || !$_SESSION['captcha_verified']) {
        return [
            'verified' => false,
            'reason' => 'Капча не пройдена'
        ];
    }
    
    // Проверяем время истечения
    $currentTime = time();
    if (isset($_SESSION['captcha_expires']) && $currentTime > $_SESSION['captcha_expires']) {
        // Очищаем просроченную сессию
        unset($_SESSION['captcha_verified']);
        unset($_SESSION['captcha_time']);
        unset($_SESSION['captcha_target']);
        unset($_SESSION['captcha_ip']);
        unset($_SESSION['captcha_expires']);
        unset($_SESSION['captcha_type']);
        
        return [
            'verified' => false,
            'reason' => 'Время действия капчи истекло'
        ];
    }
    
    // Проверяем IP адрес для дополнительной безопасности
    $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $sessionIp = $_SESSION['captcha_ip'] ?? 'unknown';
    
    if ($currentIp !== $sessionIp && $sessionIp !== 'unknown') {
        return [
            'verified' => false,
            'reason' => 'IP адрес не совпадает'
        ];
    }
    
    return [
        'verified' => true,
        'verified_at' => $_SESSION['captcha_time'] ?? null,
        'expires_at' => $_SESSION['captcha_expires'] ?? null,
        'target' => $_SESSION['captcha_target'] ?? 'miniapp',
        'captcha_type' => $_SESSION['captcha_type'] ?? 'simple'
    ];
}

function redirectToCaptcha($target = 'miniapp') {
    $captchaUrl = 'captcha.php?target=' . urlencode($target);
    
    // Если это AJAX запрос, возвращаем JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Требуется прохождение капчи',
            'redirect_url' => $captchaUrl
        ]);
        exit;
    }
    
    // Обычное перенаправление
    header('Location: ' . $captchaUrl);
    exit;
}

function requireCaptcha($target = 'miniapp') {
    $status = checkCaptchaStatus();
    
    if (!$status['verified']) {
        redirectToCaptcha($target);
    }
    
    return $status;
}

// Функция для очистки просроченных сессий капчи
function cleanupExpiredCaptcha() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $currentTime = time();
    
    if (isset($_SESSION['captcha_expires']) && $currentTime > $_SESSION['captcha_expires']) {
        unset($_SESSION['captcha_verified']);
        unset($_SESSION['captcha_time']);
        unset($_SESSION['captcha_target']);
        unset($_SESSION['captcha_ip']);
        unset($_SESSION['captcha_expires']);
        unset($_SESSION['captcha_type']);
        
        return true; // Были очищены просроченные данные
    }
    
    return false; // Ничего не было очищено
}
?> 