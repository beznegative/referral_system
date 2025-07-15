<?php
// Ключ шифрования
define('ENCRYPTION_KEY', 'your-secret-key-123');

// Функция для шифрования данных
function encryptData($data) {
    if (empty($data)) {
        return '';
    }
    
    $method = "AES-256-CBC";
    $key = substr(hash('sha256', ENCRYPTION_KEY, true), 0, 32);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
    
    if ($encrypted === false) {
        return '';
    }
    
    return base64_encode($iv . $encrypted);
}

// Функция для расшифровки данных
function decryptData($encryptedData) {
    if (empty($encryptedData)) {
        return '';
    }
    
    $method = "AES-256-CBC";
    $key = substr(hash('sha256', ENCRYPTION_KEY, true), 0, 32);
    $data = base64_decode($encryptedData);
    
    if ($data === false) {
        return '';
    }
    
    if (strlen($data) < 16) {
        return '';
    }
    
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    
    return openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
} 