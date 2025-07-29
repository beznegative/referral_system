<?php
require_once 'includes/database.php';

echo "=== Обновление структуры базы данных ===\n";

try {
    $pdo->beginTransaction();
    
    echo "1. Переименовываем существующие поля...\n";
    
    // Переименовываем существующие поля для ясности
    $sql1 = "ALTER TABLE `users` 
        CHANGE `paid_amount` `total_paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Всего выплачено в рублях',
        CHANGE `paid_for_referrals` `total_paid_for_referrals` decimal(10,2) DEFAULT 0.00 COMMENT 'Всего выплачено за рефералов'";
    
    $pdo->exec($sql1);
    echo "   ✓ Поля переименованы\n";
    
    echo "2. Добавляем новые поля для месячного учета...\n";
    
    // Добавляем новые поля для месячного учета
    $sql2 = "ALTER TABLE `users` 
        ADD `monthly_paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено в рублях за текущий месяц' AFTER `total_paid_for_referrals`,
        ADD `monthly_paid_for_referrals` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено за рефералов за текущий месяц' AFTER `monthly_paid_amount`,
        ADD `payment_month` varchar(7) DEFAULT NULL COMMENT 'Месяц выплат в формате YYYY-MM' AFTER `monthly_paid_for_referrals`";
    
    $pdo->exec($sql2);
    echo "   ✓ Новые поля добавлены\n";
    
    echo "3. Создаем таблицу для истории месячных выплат...\n";
    
    // Создаем таблицу для истории месячных выплат
    $sql3 = "CREATE TABLE IF NOT EXISTS `monthly_payments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `payment_month` varchar(7) NOT NULL COMMENT 'Месяц в формате YYYY-MM',
        `paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено в рублях за месяц',
        `paid_for_referrals` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено за рефералов за месяц',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_month` (`user_id`, `payment_month`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql3);
    echo "   ✓ Таблица monthly_payments создана\n";
    
    echo "4. Обновляем значение payment_month для существующих пользователей...\n";
    
    // Устанавливаем текущий месяц для всех пользователей
    $currentMonth = date('Y-m');
    $sql4 = "UPDATE `users` SET `payment_month` = ? WHERE `payment_month` IS NULL";
    $stmt = $pdo->prepare($sql4);
    $stmt->execute([$currentMonth]);
    echo "   ✓ Поле payment_month обновлено для всех пользователей\n";
    
    $pdo->commit();
    
    echo "\n=== ✅ ОБНОВЛЕНИЕ УСПЕШНО ЗАВЕРШЕНО ===\n";
    echo "Структура базы данных обновлена:\n";
    echo "- paid_amount → total_paid_amount\n";
    echo "- paid_for_referrals → total_paid_for_referrals\n";
    echo "- Добавлены поля для месячного учета:\n";
    echo "  * monthly_paid_amount\n";
    echo "  * monthly_paid_for_referrals\n";
    echo "  * payment_month\n";
    echo "- Создана таблица monthly_payments для архива\n";
    echo "\nТеперь вы можете использовать новый функционал!\n";
    
} catch (Exception $e) {
    try {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    } catch (Exception $rollbackException) {
        // Игнорируем ошибку отката
    }
    
    echo "\n❌ ОШИБКА ПРИ ОБНОВЛЕНИИ: " . $e->getMessage() . "\n";
    
    // Проверяем, возможно поля уже существуют
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "\n⚠️  Похоже, что обновление уже было выполнено ранее.\n";
        echo "Проверьте структуру таблицы users в базе данных.\n";
    }
    
    // Попробуем выполнить оставшиеся операции без транзакции
    try {
        echo "\n3. Создаем таблицу для истории месячных выплат...\n";
        
        $sql3 = "CREATE TABLE IF NOT EXISTS `monthly_payments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `payment_month` varchar(7) NOT NULL COMMENT 'Месяц в формате YYYY-MM',
            `paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено в рублях за месяц',
            `paid_for_referrals` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено за рефералов за месяц',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_month` (`user_id`, `payment_month`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql3);
        echo "   ✓ Таблица monthly_payments создана\n";
        
        echo "4. Обновляем значение payment_month для существующих пользователей...\n";
        
        $currentMonth = date('Y-m');
        $sql4 = "UPDATE `users` SET `payment_month` = ? WHERE `payment_month` IS NULL";
        $stmt = $pdo->prepare($sql4);
        $stmt->execute([$currentMonth]);
        echo "   ✓ Поле payment_month обновлено для всех пользователей\n";
        
        echo "\n=== ✅ ОБНОВЛЕНИЕ ЗАВЕРШЕНО (частично) ===\n";
        echo "Основные изменения были применены успешно.\n";
        
    } catch (Exception $e2) {
        echo "\nДополнительная ошибка: " . $e2->getMessage() . "\n";
    }
}
?> 