<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    // Проверяем метод запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Метод не поддерживается');
    }
    
    // Очищаем все переменные капчи из сессии
    $captchaVars = [
        'captcha_verified', 'captcha_time', 'captcha_target', 
        'captcha_ip', 'captcha_expires', 'captcha_type'
    ];
    
    $clearedVars = [];
    foreach ($captchaVars as $var) {
        if (isset($_SESSION[$var])) {
            $clearedVars[] = $var;
            unset($_SESSION[$var]);
        }
    }
    
    // Логируем очистку
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'clear_captcha',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'session_id' => session_id(),
        'cleared_vars' => $clearedVars
    ];
    
    $logFile = 'logs/captcha_actions.log';
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    
    // Возвращаем успешный результат
    echo json_encode([
        'success' => true,
        'message' => 'Данные капчи очищены',
        'cleared_variables' => $clearedVars
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 