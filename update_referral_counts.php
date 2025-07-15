<?php
// Функция для обновления количества рефералов
function updateReferralCounts($pdo, $specific_affiliate_id = null) {
    try {
        if ($specific_affiliate_id) {
            // Обновляем только конкретного партнера
            $stmt = $pdo->prepare("
                UPDATE users 
                SET referral_count = (
                    SELECT COUNT(*) 
                    FROM users AS u2 
                    WHERE u2.affiliate_id = users.id
                )
                WHERE id = ? AND is_affiliate = 1
            ");
            $stmt->execute([$specific_affiliate_id]);
        } else {
            // Обновляем всех партнеров
            $stmt = $pdo->prepare("
                UPDATE users 
                SET referral_count = (
                    SELECT COUNT(*) 
                    FROM users AS u2 
                    WHERE u2.affiliate_id = users.id
                )
                WHERE is_affiliate = 1
            ");
            $stmt->execute();
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Автоматическое обновление при подключении файла убрано
// Функция должна вызываться явно там, где это необходимо
?> 