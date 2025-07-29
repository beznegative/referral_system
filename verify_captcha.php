<?php
session_start();
require_once 'captcha_config.php';

header('Content-Type: application/json; charset=utf-8');

// Включаем отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Проверка reCAPTCHA через Google API
 */
function verifyRecaptcha($response, $remoteIp = null) {
    $secretKey = getRecaptchaSecretKey();
    
    if (empty($response)) {
        throw new Exception('reCAPTCHA токен не предоставлен');
    }
    
    $postData = [
        'secret' => $secretKey,
        'response' => $response
    ];
    
    if ($remoteIp) {
        $postData['remoteip'] = $remoteIp;
    }
    
    $postFields = http_build_query($postData);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Ошибка CURL: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('HTTP ошибка: ' . $httpCode);
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        throw new Exception('Некорректный ответ от Google reCAPTCHA');
    }
    
    if (!$result['success']) {
        $errorCodes = isset($result['error-codes']) ? implode(', ', $result['error-codes']) : 'неизвестно';
        throw new Exception('reCAPTCHA не пройдена: ' . $errorCodes);
    }
    
    return [
        'success' => true,
        'score' => $result['score'] ?? null,
        'action' => $result['action'] ?? null,
        'hostname' => $result['hostname'] ?? null,
        'challenge_ts' => $result['challenge_ts'] ?? null
    ];
}

try {
    // Проверяем метод запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Метод не поддерживается');
    }
    
    // Получаем данные из запроса
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Некорректные данные запроса');
    }
    
    // Определяем тип капчи
    $captchaType = isset($data['captcha_type']) ? $data['captcha_type'] : 'simple';
    
    // Проверяем капчу в зависимости от типа
    if ($captchaType === 'recaptcha') {
        // Проверка reCAPTCHA
        if (!isset($data['recaptcha_response']) || empty($data['recaptcha_response'])) {
            throw new Exception('reCAPTCHA токен не предоставлен');
        }
        
        $recaptchaResult = verifyRecaptcha(
            $data['recaptcha_response'], 
            $_SERVER['REMOTE_ADDR'] ?? null
        );
        
        if (!$recaptchaResult['success']) {
            throw new Exception('reCAPTCHA не пройдена');
        }
        
    } else {
        // Проверка простой капчи
        if (!isset($data['captcha_verified']) || !$data['captcha_verified']) {
            throw new Exception('Капча не пройдена');
        }
    }
    
    // Получаем целевую страницу
    $target = isset($data['target']) ? $data['target'] : 'miniapp';
    
    // Дополнительная проверка безопасности - простая математическая задача
    $currentTime = time();
    $expectedResult = ($currentTime % 10) + 5; // Число от 5 до 14
    
    // Устанавливаем сессионные переменные
    $_SESSION['captcha_verified'] = true;
    $_SESSION['captcha_time'] = $currentTime;
    $_SESSION['captcha_target'] = $target;
    $_SESSION['captcha_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $_SESSION['captcha_type'] = $captchaType;
    
    // Капча действительна в течение времени из конфигурации
    $_SESSION['captcha_expires'] = $currentTime + getCaptchaConfig()['expires_time'];
    
    // Логируем успешную верификацию
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'target' => $target,
        'session_id' => session_id(),
        'captcha_type' => $captchaType
    ];
    
    // Добавляем дополнительную информацию для reCAPTCHA
    if ($captchaType === 'recaptcha' && isset($recaptchaResult)) {
        $logData['recaptcha_hostname'] = $recaptchaResult['hostname'] ?? null;
        $logData['recaptcha_score'] = $recaptchaResult['score'] ?? null;
        $logData['recaptcha_action'] = $recaptchaResult['action'] ?? null;
    }
    
    // Создаем лог-файл если его нет
    $logFile = 'logs/captcha_verifications.log';
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    
    // Возвращаем успешный результат
    $response = [
        'success' => true,
        'message' => $captchaType === 'recaptcha' ? 'reCAPTCHA успешно пройдена' : 'Капча успешно пройдена',
        'target' => $target,
        'captcha_type' => $captchaType,
        'expires_in' => getCaptchaConfig()['expires_time']
    ];
    
    // Добавляем информацию о reCAPTCHA если применимо
    if ($captchaType === 'recaptcha' && isset($recaptchaResult)) {
        $response['recaptcha_info'] = [
            'hostname' => $recaptchaResult['hostname'] ?? null,
            'score' => $recaptchaResult['score'] ?? null
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Логируем ошибку
    $errorData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    $errorLogFile = 'logs/captcha_errors.log';
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents($errorLogFile, json_encode($errorData) . "\n", FILE_APPEND | LOCK_EX);
    
    // Возвращаем ошибку
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 