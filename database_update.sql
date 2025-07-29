-- Обновление структуры базы данных для месячного учета выплат
-- Дата: 2025-01-11

-- Переименовываем существующие поля для ясности
ALTER TABLE `users` 
CHANGE `paid_amount` `total_paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Всего выплачено в рублях',
CHANGE `paid_for_referrals` `total_paid_for_referrals` decimal(10,2) DEFAULT 0.00 COMMENT 'Всего выплачено за рефералов';

-- Добавляем новые поля для месячного учета
ALTER TABLE `users` 
ADD `monthly_paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено в рублях за текущий месяц' AFTER `total_paid_for_referrals`,
ADD `monthly_paid_for_referrals` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено за рефералов за текущий месяц' AFTER `monthly_paid_amount`,
ADD `payment_month` varchar(7) DEFAULT NULL COMMENT 'Месяц выплат в формате YYYY-MM' AFTER `monthly_paid_for_referrals`;

-- Создаем таблицу для истории месячных выплат
CREATE TABLE `monthly_payments` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 