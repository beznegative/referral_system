<?php
/**
 * Скрипт для инициализации таблицы referral_settings
 * Создает таблицу и добавляет настройки по умолчанию
 */

require_once 'includes/database.php';

try {
    echo "Инициализация таблицы referral_settings...\n";
    
    // Создаем таблицу настроек реферальной системы
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS referral_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_name VARCHAR(50) NOT NULL UNIQUE,
            setting_value DECIMAL(5,2) NOT NULL,
            description VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($createTableQuery);
    echo "Таблица referral_settings создана или уже существует.\n";
    
    // Вставляем настройки по умолчанию
    $insertQuery = "
        INSERT INTO referral_settings (setting_name, setting_value, description) VALUES 
        ('level_1_percent', 50.00, 'Процент для рефералов 1 уровня'),
        ('level_2_percent', 25.00, 'Процент для рефералов 2 уровня'),
        ('level_3_percent', 10.00, 'Процент для рефералов 3 уровня')
        ON DUPLICATE KEY UPDATE 
        setting_value = VALUES(setting_value),
        description = VALUES(description)
    ";
    
    $pdo->exec($insertQuery);
    echo "Настройки по умолчанию добавлены/обновлены.\n";
    
    // Проверяем, что настройки добавлены
    $stmt = $pdo->query("SELECT setting_name, setting_value, description FROM referral_settings ORDER BY setting_name");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nТекущие настройки:\n";
    foreach ($settings as $setting) {
        echo "- {$setting['setting_name']}: {$setting['setting_value']}% - {$setting['description']}\n";
    }
    
    echo "\nИнициализация завершена успешно!\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?> 