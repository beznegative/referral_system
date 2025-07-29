<?php

// Параметры подключения к базе данных
$db_host = 'localhost';      // Хост базы данных
$db_name = 'referral_system'; // Имя базы данных
$db_user = 'root';           // Имя пользователя (для VPS: 'referral_user')
$db_pass = '';               // Пароль (для VPS: укажите пароль пользователя)
$db_charset = 'utf8mb4';     // Кодировка базы данных

// ДЛЯ VPS СЕРВЕРА ИЗМЕНИТЕ НАСТРОЙКИ НА:
// $db_host = 'localhost';
// $db_name = 'referral_system';
// $db_user = 'referral_user';
// $db_pass = 'ваш_пароль_здесь';
// $db_charset = 'utf8mb4';

try {
    // Формирование DSN (Data Source Name)
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    
    // Опции PDO для обработки ошибок и установки режима выборки
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Выбрасывать исключения при ошибках
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Возвращать данные в виде ассоциативного массива
        PDO::ATTR_EMULATE_PREPARES   => false,                    // Отключить эмуляцию подготовленных выражений
    ];

    // Создание подключения к базе данных
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

} catch (PDOException $e) {
    // В случае ошибки подключения выводим сообщение и прекращаем выполнение скрипта
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
} 