<?php
require_once 'includes/database.php';
require_once 'referral_calculator.php';

echo "<h1>Тестирование реферальной системы</h1>\n";

try {
    $pdo->beginTransaction();
    
    // Создаем тестовых пользователей
    
    // 1. Иван (партнер)
    $ivan_stmt = $pdo->prepare("
        INSERT INTO users (full_name, bank_card, telegram_username, telegram_id, phone_number, birth_date, is_affiliate, paid_amount, paid_for_referrals)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ivan_stmt->execute([
        'Иван Иванов',
        'encrypted_card_ivan', 
        '@ivan',
        123456789,
        '89051234567',
        '1990-01-01',
        1, // is_affiliate
        0.00,
        0.00
    ]);
    $ivan_id = $pdo->lastInsertId();
    echo "Создан партнер Иван (ID: $ivan_id)<br>\n";
    
    // 2. Вася (реферал Ивана)
    $vasya_stmt = $pdo->prepare("
        INSERT INTO users (full_name, bank_card, telegram_username, telegram_id, phone_number, birth_date, is_affiliate, affiliate_id, paid_amount, paid_for_referrals)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $vasya_stmt->execute([
        'Вася Петров',
        'encrypted_card_vasya',
        '@vasya',
        123456790,
        '89051234568',
        '1992-01-01',
        0, // is_affiliate
        $ivan_id, // affiliate_id
        0.00,
        0.00
    ]);
    $vasya_id = $pdo->lastInsertId();
    echo "Создан пользователь Вася (ID: $vasya_id), партнер: Иван<br>\n";
    
    // 3. Первый человек, которого привел Вася
    $user1_stmt = $pdo->prepare("
        INSERT INTO users (full_name, bank_card, telegram_username, telegram_id, phone_number, birth_date, is_affiliate, affiliate_id, paid_amount, paid_for_referrals)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $user1_stmt->execute([
        'Первый пользователь',
        'encrypted_card_user1',
        '@user1',
        123456791,
        '89051234569',
        '1993-01-01',
        0, // is_affiliate
        $vasya_id, // affiliate_id
        0.00,
        0.00
    ]);
    $user1_id = $pdo->lastInsertId();
    echo "Создан пользователь 1 (ID: $user1_id), партнер: Вася<br>\n";
    
    // 4. Второй человек, которого привел Вася
    $user2_stmt = $pdo->prepare("
        INSERT INTO users (full_name, bank_card, telegram_username, telegram_id, phone_number, birth_date, is_affiliate, affiliate_id, paid_amount, paid_for_referrals)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $user2_stmt->execute([
        'Второй пользователь',
        'encrypted_card_user2',
        '@user2',
        123456792,
        '89051234570',
        '1994-01-01',
        0, // is_affiliate
        $vasya_id, // affiliate_id
        0.00,
        0.00
    ]);
    $user2_id = $pdo->lastInsertId();
    echo "Создан пользователь 2 (ID: $user2_id), партнер: Вася<br>\n";
    
    // 5. Третий уровень - человек, которого привел первый пользователь
    $user3_stmt = $pdo->prepare("
        INSERT INTO users (full_name, bank_card, telegram_username, telegram_id, phone_number, birth_date, is_affiliate, affiliate_id, paid_amount, paid_for_referrals)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $user3_stmt->execute([
        'Третий пользователь',
        'encrypted_card_user3',
        '@user3',
        123456793,
        '89051234571',
        '1995-01-01',
        0, // is_affiliate
        $user1_id, // affiliate_id
        0.00,
        0.00
    ]);
    $user3_id = $pdo->lastInsertId();
    echo "Создан пользователь 3 (ID: $user3_id), партнер: Первый пользователь<br>\n";
    
    $pdo->commit();
    
    echo "<h2>Тестирование сценария</h2>\n";
    
    // Тест 1: Васе выплачивают 5000, Иван должен получить 2500 (50%)
    echo "<h3>Тест 1: Васе выплачивают 5000</h3>\n";
    $pdo->prepare("UPDATE users SET paid_amount = ? WHERE id = ?")->execute([5000, $vasya_id]);
    updateAffiliatePayments($pdo, $vasya_id);
    
    // Проверяем результат
    $ivan_earnings = $pdo->prepare("SELECT paid_for_referrals FROM users WHERE id = ?");
    $ivan_earnings->execute([$ivan_id]);
    $ivan_result = $ivan_earnings->fetch(PDO::FETCH_ASSOC);
    
    echo "Иван получил за рефералов: " . $ivan_result['paid_for_referrals'] . " ₽ (ожидалось: 2500 ₽)<br>\n";
    
    // Тест 2: Первому и второму пользователю выплачивают по 1000
    echo "<h3>Тест 2: Первому и второму пользователю выплачивают по 1000</h3>\n";
    $pdo->prepare("UPDATE users SET paid_amount = ? WHERE id = ?")->execute([1000, $user1_id]);
    $pdo->prepare("UPDATE users SET paid_amount = ? WHERE id = ?")->execute([1000, $user2_id]);
    updateAffiliatePayments($pdo, $user1_id);
    updateAffiliatePayments($pdo, $user2_id);
    
    // Проверяем результат для Васи (должен получить 1000 - 50% от 2*1000)
    $vasya_earnings = $pdo->prepare("SELECT paid_for_referrals FROM users WHERE id = ?");
    $vasya_earnings->execute([$vasya_id]);
    $vasya_result = $vasya_earnings->fetch(PDO::FETCH_ASSOC);
    
    // Проверяем результат для Ивана (должен получить дополнительно 500 - 25% от 2*1000)
    $ivan_earnings = $pdo->prepare("SELECT paid_for_referrals FROM users WHERE id = ?");
    $ivan_earnings->execute([$ivan_id]);
    $ivan_result = $ivan_earnings->fetch(PDO::FETCH_ASSOC);
    
    echo "Вася получил за рефералов: " . $vasya_result['paid_for_referrals'] . " ₽ (ожидалось: 1000 ₽)<br>\n";
    echo "Иван получил за рефералов: " . $ivan_result['paid_for_referrals'] . " ₽ (ожидалось: 3000 ₽)<br>\n";
    
    // Тест 3: Третьему пользователю выплачивают 1000
    echo "<h3>Тест 3: Третьему пользователю выплачивают 1000</h3>\n";
    $pdo->prepare("UPDATE users SET paid_amount = ? WHERE id = ?")->execute([1000, $user3_id]);
    updateAffiliatePayments($pdo, $user3_id);
    
    // Проверяем результаты
    $user1_earnings = $pdo->prepare("SELECT paid_for_referrals FROM users WHERE id = ?");
    $user1_earnings->execute([$user1_id]);
    $user1_result = $user1_earnings->fetch(PDO::FETCH_ASSOC);
    
    $vasya_earnings = $pdo->prepare("SELECT paid_for_referrals FROM users WHERE id = ?");
    $vasya_earnings->execute([$vasya_id]);
    $vasya_result = $vasya_earnings->fetch(PDO::FETCH_ASSOC);
    
    $ivan_earnings = $pdo->prepare("SELECT paid_for_referrals FROM users WHERE id = ?");
    $ivan_earnings->execute([$ivan_id]);
    $ivan_result = $ivan_earnings->fetch(PDO::FETCH_ASSOC);
    
    echo "Первый пользователь получил за рефералов: " . $user1_result['paid_for_referrals'] . " ₽ (ожидалось: 500 ₽)<br>\n";
    echo "Вася получил за рефералов: " . $vasya_result['paid_for_referrals'] . " ₽ (ожидалось: 1250 ₽)<br>\n";
    echo "Иван получил за рефералов: " . $ivan_result['paid_for_referrals'] . " ₽ (ожидалось: 3100 ₽)<br>\n";
    
    echo "<h2>Детальная информация по выплатам</h2>\n";
    
    // Показываем детальную информацию из таблицы referral_earnings
    $earnings_stmt = $pdo->query("
        SELECT re.*, 
               a.full_name as affiliate_name,
               r.full_name as referral_name
        FROM referral_earnings re
        JOIN users a ON re.affiliate_id = a.id
        JOIN users r ON re.referral_id = r.id
        ORDER BY re.affiliate_id, re.level
    ");
    
    echo "<table border='1'>\n";
    echo "<tr><th>Партнер</th><th>Реферал</th><th>Уровень</th><th>Выплата</th></tr>\n";
    
    while ($earning = $earnings_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars($earning['affiliate_name']) . "</td>\n";
        echo "<td>" . htmlspecialchars($earning['referral_name']) . "</td>\n";
        echo "<td>" . $earning['level'] . "</td>\n";
        echo "<td>" . $earning['earning'] . " ₽</td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<h2>Тестирование завершено!</h2>\n";
    
} catch (Exception $e) {
    // Проверяем, есть ли активная транзакция перед откатом
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?> 