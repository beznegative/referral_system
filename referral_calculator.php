<?php

/**
 * Функции для расчета выплат по реферальной системе
 */

/**
 * Получение настроек процентов из базы данных
 */
function getReferralSettings($pdo) {
    try {
        // Проверяем, существует ли таблица referral_settings
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_settings'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            // Создаем таблицу если она не существует
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
            
            // Выполняем создание таблицы безопасно
            if (!$pdo->inTransaction()) {
                $pdo->exec($createTableQuery);
            } else {
                // Если есть активная транзакция, пытаемся создать таблицу в рамках этой транзакции
                $pdo->exec($createTableQuery);
            }
            
            // Добавляем настройки по умолчанию
            $insertQuery = "
                INSERT INTO referral_settings (setting_name, setting_value, description) VALUES 
                ('level_1_percent', 50.00, 'Процент для рефералов 1 уровня'),
                ('level_2_percent', 25.00, 'Процент для рефералов 2 уровня'),
                ('level_3_percent', 10.00, 'Процент для рефералов 3 уровня')
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ";
            $pdo->exec($insertQuery);
        }
        
        $stmt = $pdo->query("SELECT setting_name, setting_value FROM referral_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
        
        // Добавляем значения по умолчанию если они отсутствуют
        $defaultSettings = [
            'level_1_percent' => 50.00,
            'level_2_percent' => 25.00,
            'level_3_percent' => 10.00
        ];
        
        foreach ($defaultSettings as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
                // Добавляем в базу данных если отсутствует
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO referral_settings (setting_name, setting_value, description) 
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                    ");
                    $stmt->execute([$key, $value, 'Процент для рефералов ' . substr($key, 6, 1) . ' уровня']);
                } catch (Exception $insertException) {
                    // Если вставка не удалась, логируем ошибку но продолжаем работу
                    error_log('Ошибка при добавлении настройки ' . $key . ': ' . $insertException->getMessage());
                }
            }
        }
        
        return $settings;
        
    } catch (Exception $e) {
        // В случае ошибки возвращаем значения по умолчанию
        return [
            'level_1_percent' => 50.00,
            'level_2_percent' => 25.00,
            'level_3_percent' => 10.00
        ];
    }
}

/**
 * Получение рефералов пользователя по уровням
 */
function getReferralsByLevels($pdo, $userId, $maxLevel = 3) {
    $referrals = [];
    
    // Получаем рефералов 1 уровня
    $stmt = $pdo->prepare("
        SELECT id, full_name, total_paid_amount 
        FROM users 
        WHERE affiliate_id = ? AND is_affiliate = 0
    ");
    $stmt->execute([$userId]);
    $level1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $referrals[1] = $level1;
    
    if ($maxLevel >= 2) {
        // Получаем рефералов 2 уровня
        $level2 = [];
        foreach ($level1 as $ref1) {
            $stmt = $pdo->prepare("
                SELECT id, full_name, total_paid_amount 
                FROM users 
                WHERE affiliate_id = ? AND is_affiliate = 0
            ");
            $stmt->execute([$ref1['id']]);
            $level2 = array_merge($level2, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        $referrals[2] = $level2;
    }
    
    if ($maxLevel >= 3) {
        // Получаем рефералов 3 уровня
        $level3 = [];
        foreach ($referrals[2] as $ref2) {
            $stmt = $pdo->prepare("
                SELECT id, full_name, total_paid_amount 
                FROM users 
                WHERE affiliate_id = ? AND is_affiliate = 0
            ");
            $stmt->execute([$ref2['id']]);
            $level3 = array_merge($level3, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        $referrals[3] = $level3;
    }
    
    return $referrals;
}

/**
 * Расчет выплат по уровням реферальной системы
 */
function calculateReferralEarnings($pdo, $userId) {
    $settings = getReferralSettings($pdo);
    $referrals = getReferralsByLevels($pdo, $userId, 3);
    
    $earnings = [
        'level_1' => 0,
        'level_2' => 0,
        'level_3' => 0,
        'total' => 0
    ];
    
    // Расчет за рефералов 1 уровня
    if (isset($referrals[1])) {
        foreach ($referrals[1] as $referral) {
            $percent = isset($settings['level_1_percent']) ? $settings['level_1_percent'] : 50.00;
            $earnings['level_1'] += $referral['total_paid_amount'] * ($percent / 100);
        }
    }
    
    // Расчет за рефералов 2 уровня
    if (isset($referrals[2])) {
        foreach ($referrals[2] as $referral) {
            $percent = isset($settings['level_2_percent']) ? $settings['level_2_percent'] : 25.00;
            $earnings['level_2'] += $referral['total_paid_amount'] * ($percent / 100);
        }
    }
    
    // Расчет за рефералов 3 уровня
    if (isset($referrals[3])) {
        foreach ($referrals[3] as $referral) {
            $percent = isset($settings['level_3_percent']) ? $settings['level_3_percent'] : 10.00;
            $earnings['level_3'] += $referral['total_paid_amount'] * ($percent / 100);
        }
    }
    
    $earnings['total'] = $earnings['level_1'] + $earnings['level_2'] + $earnings['level_3'];
    
    return $earnings;
}

/**
 * Получение детальной информации о рефералах с выплатами
 */
function getReferralDetails($pdo, $userId) {
    $settings = getReferralSettings($pdo);
    $referrals = getReferralsByLevels($pdo, $userId, 3);
    
    $details = [];
    
    // Детали по 1 уровню
    if (isset($referrals[1])) {
        foreach ($referrals[1] as $referral) {
            $percent = isset($settings['level_1_percent']) ? $settings['level_1_percent'] : 50.00;
            $earning = $referral['total_paid_amount'] * ($percent / 100);
            $details[] = [
                'level' => 1,
                'name' => $referral['full_name'],
                'paid_amount' => $referral['total_paid_amount'],
                'earning' => $earning,
                'percent' => $percent
            ];
        }
    }
    
    // Детали по 2 уровню
    if (isset($referrals[2])) {
        foreach ($referrals[2] as $referral) {
            $percent = isset($settings['level_2_percent']) ? $settings['level_2_percent'] : 25.00;
            $earning = $referral['total_paid_amount'] * ($percent / 100);
            $details[] = [
                'level' => 2,
                'name' => $referral['full_name'],
                'paid_amount' => $referral['total_paid_amount'],
                'earning' => $earning,
                'percent' => $percent
            ];
        }
    }
    
    // Детали по 3 уровню
    if (isset($referrals[3])) {
        foreach ($referrals[3] as $referral) {
            $percent = isset($settings['level_3_percent']) ? $settings['level_3_percent'] : 10.00;
            $earning = $referral['total_paid_amount'] * ($percent / 100);
            $details[] = [
                'level' => 3,
                'name' => $referral['full_name'],
                'paid_amount' => $referral['total_paid_amount'],
                'earning' => $earning,
                'percent' => $percent
            ];
        }
    }
    
    return $details;
}

/**
 * Обновление настроек реферальной системы
 */
function updateReferralSettings($pdo, $level1, $level2, $level3) {
    $ownTransaction = false;
    
    // Проверяем, есть ли уже активная транзакция
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $ownTransaction = true;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE referral_settings SET setting_value = ? WHERE setting_name = ?");
        $stmt->execute([$level1, 'level_1_percent']);
        $stmt->execute([$level2, 'level_2_percent']);
        $stmt->execute([$level3, 'level_3_percent']);
        
        // Коммитим только если мы создали собственную транзакцию
        if ($ownTransaction) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        // Откатываем только если мы создали собственную транзакцию
        if ($ownTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Автоматическое обновление выплат партнерам при изменении total_paid_amount реферала
 */
function updateAffiliatePayments($pdo, $userId) {
    try {
        $settings = getReferralSettings($pdo);
        
        // Получаем информацию о пользователе
        $stmt = $pdo->prepare("SELECT id, affiliate_id, total_paid_amount FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !$user['affiliate_id']) {
            return; // Нет партнера
        }
        
        // Находим всех партнеров в цепочке (до 3 уровней)
        $affiliates = [];
        $currentAffiliateId = $user['affiliate_id'];
        $level = 1;
        
        while ($currentAffiliateId && $level <= 3) {
            $stmt = $pdo->prepare("SELECT id, affiliate_id FROM users WHERE id = ?");
            $stmt->execute([$currentAffiliateId]);
            $affiliate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($affiliate) {
                $affiliates[] = [
                    'id' => $affiliate['id'],
                    'level' => $level
                ];
                $currentAffiliateId = $affiliate['affiliate_id'];
                $level++;
            } else {
                break;
            }
        }
        
        // Обновляем выплаты для каждого партнера
        foreach ($affiliates as $affiliate) {
            $percentKey = 'level_' . $affiliate['level'] . '_percent';
            $percent = isset($settings[$percentKey]) ? $settings[$percentKey] : 0;
            
            // Рассчитываем выплату для этого партнера от данного реферала
            $earning = $user['total_paid_amount'] * ($percent / 100);
            
            // Обновляем выплату партнера
            updateAffiliateEarning($pdo, $affiliate['id'], $earning, $userId, $affiliate['level']);
        }
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Обновление выплаты конкретного партнера от конкретного реферала
 */
function updateAffiliateEarning($pdo, $affiliateId, $earning, $referralId, $level) {
    try {
        // Создаем таблицу для отслеживания выплат, если она не существует
        $createTableQuery = "
            CREATE TABLE IF NOT EXISTS referral_earnings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                affiliate_id INT NOT NULL,
                referral_id INT NOT NULL,
                level INT NOT NULL,
                earning DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_earning (affiliate_id, referral_id, level),
                FOREIGN KEY (affiliate_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (referral_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        // Выполняем создание таблицы только если нет активной транзакции
        // или если мы уверены, что это безопасно
        if (!$pdo->inTransaction()) {
            $pdo->exec($createTableQuery);
        } else {
            // Проверяем, существует ли таблица
            $stmt = $pdo->query("SHOW TABLES LIKE 'referral_earnings'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec($createTableQuery);
            }
        }
        
        // Обновляем или вставляем запись о выплате
        $stmt = $pdo->prepare("
            INSERT INTO referral_earnings (affiliate_id, referral_id, level, earning)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE earning = VALUES(earning), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$affiliateId, $referralId, $level, $earning]);
        
        // Пересчитываем общую сумму выплат за рефералов для партнера
        $stmt = $pdo->prepare("
            UPDATE users 
            SET total_paid_for_referrals = (
                SELECT COALESCE(SUM(earning), 0) 
                FROM referral_earnings 
                WHERE affiliate_id = ?
            )
            WHERE id = ?
        ");
        $stmt->execute([$affiliateId, $affiliateId]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Обновление месячных выплат за рефералов для всех партнёров за конкретный месяц
 * Пересчитывает все месячные выплаты за рефералов для указанного месяца
 */
function updateMonthlyAffiliatePayments($pdo, $paymentMonth) {
    try {
        $settings = getReferralSettings($pdo);
        
        // Сбрасываем все месячные выплаты за рефералов для этого месяца
        $stmt = $pdo->prepare("
            UPDATE monthly_payments 
            SET paid_for_referrals = 0.00 
            WHERE payment_month = ?
        ");
        $stmt->execute([$paymentMonth]);
        
        // Также обновляем monthly_paid_for_referrals в users для тех, у кого payment_month совпадает
        $stmt = $pdo->prepare("
            UPDATE users 
            SET monthly_paid_for_referrals = 0.00 
            WHERE payment_month = ?
        ");
        $stmt->execute([$paymentMonth]);
        
        // Получаем всех пользователей с выплатами за этот месяц
        $stmt = $pdo->prepare("
            SELECT mp.user_id, mp.paid_amount, u.affiliate_id
            FROM monthly_payments mp
            JOIN users u ON mp.user_id = u.id
            WHERE mp.payment_month = ? AND mp.paid_amount > 0 AND u.affiliate_id IS NOT NULL
        ");
        $stmt->execute([$paymentMonth]);
        $usersWithPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Для каждого пользователя с выплатами рассчитываем выплаты его партнёрам
        foreach ($usersWithPayments as $userPayment) {
            $userId = $userPayment['user_id'];
            $paidAmount = $userPayment['paid_amount'];
            $currentAffiliateId = $userPayment['affiliate_id'];
            $level = 1;
            
            // Проходим по цепочке партнёров (до 3 уровней)
            while ($currentAffiliateId && $level <= 3) {
                $stmt = $pdo->prepare("SELECT id, affiliate_id FROM users WHERE id = ?");
                $stmt->execute([$currentAffiliateId]);
                $affiliate = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($affiliate) {
                    $percentKey = 'level_' . $level . '_percent';
                    $percent = isset($settings[$percentKey]) ? $settings[$percentKey] : 0;
                    
                    // Рассчитываем выплату для этого партнёра
                    $earning = $paidAmount * ($percent / 100);
                    
                    if ($earning > 0) {
                        // Добавляем выплату к месячным выплатам партнёра
                        $stmt = $pdo->prepare("
                            INSERT INTO monthly_payments (user_id, payment_month, paid_amount, paid_for_referrals) 
                            VALUES (?, ?, 0.00, ?)
                            ON DUPLICATE KEY UPDATE 
                            paid_for_referrals = paid_for_referrals + VALUES(paid_for_referrals)
                        ");
                        $stmt->execute([$affiliate['id'], $paymentMonth, $earning]);
                        
                        // Если это текущий месяц партнёра, обновляем и monthly_paid_for_referrals в users
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET monthly_paid_for_referrals = monthly_paid_for_referrals + ?
                            WHERE id = ? AND payment_month = ?
                        ");
                        $stmt->execute([$earning, $affiliate['id'], $paymentMonth]);
                    }
                    
                    $currentAffiliateId = $affiliate['affiliate_id'];
                    $level++;
                } else {
                    break;
                }
            }
        }
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Пересчет всех выплат в системе
 */
function recalculateAllReferralPayments($pdo) {
    $ownTransaction = false;
    
    // Проверяем, есть ли уже активная транзакция
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $ownTransaction = true;
    }
    
    try {
        // Очищаем таблицу выплат
        $pdo->exec("DELETE FROM referral_earnings");
        
        // Сбрасываем все выплаты за рефералов
        $pdo->exec("UPDATE users SET total_paid_for_referrals = 0.00");
        
        // Получаем всех пользователей с total_paid_amount > 0
        $stmt = $pdo->query("SELECT id FROM users WHERE total_paid_amount > 0");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Пересчитываем выплаты для каждого пользователя
        foreach ($users as $userId) {
            updateAffiliatePayments($pdo, $userId);
        }
        
        // Коммитим только если мы создали собственную транзакцию
        if ($ownTransaction) {
            $pdo->commit();
        }
        
    } catch (Exception $e) {
        // Откатываем только если мы создали собственную транзакцию
        if ($ownTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
} 