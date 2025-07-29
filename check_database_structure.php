<?php
require_once 'includes/database.php';

echo "=== Проверка структуры таблицы users ===\n\n";

try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Текущие поля в таблице users:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-25s %-20s %-10s %-5s %-10s %s\n", "Field", "Type", "Null", "Key", "Default", "Extra");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        printf("%-25s %-20s %-10s %-5s %-10s %s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default'] ?: 'NULL', 
            $column['Extra']
        );
    }
    
    echo "\n" . str_repeat("-", 80) . "\n";
    
    // Проверим наличие новых полей
    $requiredFields = [
        'total_paid_amount',
        'total_paid_for_referrals', 
        'monthly_paid_amount',
        'monthly_paid_for_referrals',
        'payment_month'
    ];
    
    echo "\nПроверка наличия новых полей:\n";
    echo str_repeat("-", 40) . "\n";
    
    $existingFields = array_column($columns, 'Field');
    
    foreach ($requiredFields as $field) {
        $exists = in_array($field, $existingFields);
        $status = $exists ? "✓ Есть" : "❌ Отсутствует";
        printf("%-30s %s\n", $field, $status);
    }
    
    // Проверим таблицу monthly_payments
    echo "\n=== Проверка таблицы monthly_payments ===\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'monthly_payments'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Таблица monthly_payments существует\n\n";
        
        $stmt = $pdo->query("DESCRIBE monthly_payments");
        $monthlyColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Поля в таблице monthly_payments:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-25s %-20s %-10s %-5s %-10s %s\n", "Field", "Type", "Null", "Key", "Default", "Extra");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($monthlyColumns as $column) {
            printf("%-25s %-20s %-10s %-5s %-10s %s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null'], 
                $column['Key'], 
                $column['Default'] ?: 'NULL', 
                $column['Extra']
            );
        }
    } else {
        echo "❌ Таблица monthly_payments не существует\n";
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?> 