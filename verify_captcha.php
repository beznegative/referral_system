<?php
/**
 * Скрипт для верификации капчи и установки сессии
 */

// Начинаем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'captcha_config.php';

// Устанавливаем заголовки для JSON API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Функция для отправки JSON ответа
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Метод не поддерживается'], 405);
}

try {
    // Получаем JSON данные
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['success' => false, 'message' => 'Некорректный JSON'], 400);
    }
    
    $target = $data['target'] ?? 'miniapp';
    $captchaType = $data['captcha_type'] ?? 'simple';
    
    // Валидация целевой страницы
    $allowedTargets = ['miniapp', 'test', 'settings'];
    if (!in_array($target, $allowedTargets)) {
        sendJsonResponse(['success' => false, 'message' => 'Некорректная целевая страница'], 400);
    }
    
    $verified = false;
    $errorMessage = '';
    
    if ($captchaType === 'recaptcha') {
        // Проверка reCAPTCHA
        $recaptchaResponse = $data['recaptcha_response'] ?? '';
        
        if (empty($recaptchaResponse)) {
            sendJsonResponse(['success' => false, 'message' => 'reCAPTCHA ответ не предоставлен'], 400);
        }
        
        // Проверяем reCAPTCHA на сервере Google
        $secretKey = RECAPTCHA_SECRET_KEY;
        $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';
        
        $postData = [
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($postData),
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents($verifyURL, false, $context);
        
        if ($response === false) {
            sendJsonResponse(['success' => false, 'message' => 'Ошибка подключения к сервису reCAPTCHA'], 500);
        }
        
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendJsonResponse(['success' => false, 'message' => 'Некорректный ответ от сервиса reCAPTCHA'], 500);
        }
        
        if ($responseData['success']) {
            $verified = true;
        } else {
            $errorCodes = $responseData['error-codes'] ?? [];
            $errorMessage = 'reCAPTCHA проверка не пройдена';
            
            // Логируем ошибки для отладки
            if (!empty($errorCodes)) {
                error_log('reCAPTCHA errors: ' . implode(', ', $errorCodes));
            }
        }
        
    } else {
        // Простая капча (нажатие кнопки)
        if (isset($data['captcha_verified']) && $data['captcha_verified'] === true) {
            $verified = true;
        } else {
            $errorMessage = 'Капча не пройдена';
        }
    }
    
    if ($verified) {
        // Устанавливаем сессионные переменные
        $_SESSION['captcha_verified'] = true;
        $_SESSION['captcha_time'] = time();
        $_SESSION['captcha_target'] = $target;
        $_SESSION['captcha_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $_SESSION['captcha_type'] = $captchaType;
        
        // Устанавливаем время истечения (30 минут)
        $_SESSION['captcha_expires'] = time() + (30 * 60);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Капча успешно пройдена',
            'expires_at' => $_SESSION['captcha_expires']
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => $errorMessage ?: 'Капча не пройдена'
        ], 400);
    }
    
} catch (Exception $e) {
    error_log('Ошибка в verify_captcha.php: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Внутренняя ошибка сервера'], 500);
}
?>
