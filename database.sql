-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Июл 29 2025 г., 21:14
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `referral_system`
--

-- --------------------------------------------------------

--
-- Структура таблицы `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL,
  `token_name` varchar(100) NOT NULL,
  `token_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `api_tokens`
--

INSERT INTO `api_tokens` (`id`, `token_name`, `token_value`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'telegram_bot_token', '7918934050:AAG-z4UDjnkDqhdB7_5PbXYB-fBEs9UiHxM', 'Токен для Telegram бота', 1, '2025-07-15 13:41:48', '2025-07-15 13:41:48');

-- --------------------------------------------------------

--
-- Структура таблицы `bookmakers`
--

CREATE TABLE `bookmakers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `bookmakers`
--

INSERT INTO `bookmakers` (`id`, `name`, `code`, `created_at`) VALUES
(1, '1xСтавка', '1xstavka', '2025-07-10 10:52:42'),
(2, 'Betboom', 'betboom', '2025-07-10 10:52:42'),
(3, 'Winline', 'winline', '2025-07-10 10:52:42'),
(4, 'Фонбет', 'fonbet', '2025-07-10 10:52:42'),
(5, 'Лига Ставок', 'ligastavok', '2025-07-10 10:52:42'),
(6, 'Марафон', 'marathon', '2025-07-10 10:52:42'),
(7, 'Пари', 'pari', '2025-07-10 10:52:42'),
(8, 'Мелбет', 'melbet', '2025-07-10 10:52:42'),
(9, 'Бетсити', 'betcity', '2025-07-10 10:52:42'),
(10, 'Зенит', 'zenit', '2025-07-10 10:52:42'),
(11, 'Pin Up', 'pinup', '2025-07-10 10:52:42'),
(12, 'Олимп', 'olimp', '2025-07-10 10:52:42'),
(13, 'Tennisi', 'tennisi', '2025-07-10 10:52:42'),
(14, 'БЕТМАСТЕР', 'betmaster', '2025-07-10 10:52:42'),
(15, 'Астрабет', 'astrabet', '2025-07-10 10:52:42');

-- --------------------------------------------------------

--
-- Структура таблицы `monthly_payments`
--

CREATE TABLE `monthly_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_month` varchar(7) NOT NULL COMMENT 'Месяц в формате YYYY-MM',
  `paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено в рублях за месяц',
  `paid_for_referrals` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено за рефералов за месяц',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `monthly_payments`
--

INSERT INTO `monthly_payments` (`id`, `user_id`, `payment_month`, `paid_amount`, `paid_for_referrals`, `created_at`, `updated_at`) VALUES
(2, 29, '2025-07', 5000.00, 0.00, '2025-07-29 17:53:53', '2025-07-29 17:53:53'),
(3, 30, '2025-07', 3000.00, 0.00, '2025-07-29 17:55:06', '2025-07-29 17:55:06'),
(4, 31, '2025-07', 3000.00, 0.00, '2025-07-29 17:55:18', '2025-07-29 17:55:18'),
(5, 29, '2025-06', 5000.00, 0.00, '2025-07-29 17:56:30', '2025-07-29 17:56:30'),
(6, 30, '2025-06', 3000.00, 0.00, '2025-07-29 17:56:30', '2025-07-29 17:56:30'),
(7, 31, '2025-06', 3000.00, 0.00, '2025-07-29 17:56:30', '2025-07-29 17:56:30');

-- --------------------------------------------------------

--
-- Структура таблицы `referral_earnings`
--

CREATE TABLE `referral_earnings` (
  `id` int(11) NOT NULL,
  `affiliate_id` int(11) NOT NULL,
  `referral_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `earning` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `referral_earnings`
--

INSERT INTO `referral_earnings` (`id`, `affiliate_id`, `referral_id`, `level`, `earning`, `created_at`, `updated_at`) VALUES
(68, 24, 29, 1, 2000.00, '2025-07-29 19:13:57', '2025-07-29 19:13:57'),
(69, 29, 30, 1, 1200.00, '2025-07-29 19:13:57', '2025-07-29 19:13:57'),
(70, 24, 30, 2, 750.00, '2025-07-29 19:13:57', '2025-07-29 19:13:57'),
(71, 30, 31, 1, 800.00, '2025-07-29 19:13:57', '2025-07-29 19:13:57'),
(72, 29, 31, 2, 500.00, '2025-07-29 19:13:57', '2025-07-29 19:13:57'),
(73, 24, 31, 3, 200.00, '2025-07-29 19:13:57', '2025-07-29 19:13:57');

-- --------------------------------------------------------

--
-- Структура таблицы `referral_settings`
--

CREATE TABLE `referral_settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` decimal(5,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `referral_settings`
--

INSERT INTO `referral_settings` (`id`, `setting_name`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(7, 'level_1_percent', 40.00, 'Процент для рефералов 1 уровня', '2025-07-15 12:35:48', '2025-07-29 19:13:47'),
(8, 'level_2_percent', 25.00, 'Процент для рефералов 2 уровня', '2025-07-15 12:35:48', '2025-07-15 12:35:48'),
(9, 'level_3_percent', 10.00, 'Процент для рефералов 3 уровня', '2025-07-15 12:35:48', '2025-07-15 12:35:48');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `bank_card` text DEFAULT NULL COMMENT 'Зашифрованный номер банковской карты (необязательное поле)',
  `telegram_username` varchar(255) NOT NULL,
  `telegram_id` bigint(20) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `birth_date` date NOT NULL,
  `is_affiliate` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `affiliate_id` int(11) DEFAULT NULL,
  `total_paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Всего выплачено в рублях',
  `referral_count` int(11) DEFAULT 0 COMMENT 'Количество рефералов',
  `total_paid_for_referrals` decimal(10,2) DEFAULT 0.00 COMMENT 'Всего выплачено за рефералов',
  `monthly_paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено в рублях за текущий месяц',
  `monthly_paid_for_referrals` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплачено за рефералов за текущий месяц',
  `payment_month` varchar(7) DEFAULT NULL COMMENT 'Месяц выплат в формате YYYY-MM'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `full_name`, `bank_card`, `telegram_username`, `telegram_id`, `phone_number`, `birth_date`, `is_affiliate`, `created_at`, `affiliate_id`, `total_paid_amount`, `referral_count`, `total_paid_for_referrals`, `monthly_paid_amount`, `monthly_paid_for_referrals`, `payment_month`) VALUES
(24, 'Админ', 'tfZ1rG4JLwLGoIka6YKT4+Ynirlzssst9COOanP03C7QPrUiK/uA7JHQXrABz4JT', '@sergey_partner', 430892673, '88005553535', '1985-03-15', 1, '2025-07-15 12:30:14', NULL, 0.00, 1, 2950.00, 0.00, 0.00, '2025-07'),
(29, 'Первый', '4wQj2mTNWNG9YW5TkaA0E4j1Ggq0PPHzeLRwOLP+uVCdHo6zG22DF+NNrTWp7izH', '@andrey_ivanov', 222222222, '88005554545', '1990-05-20', 0, '2025-07-15 12:32:04', 24, 5000.00, 2, 1700.00, 5000.00, 0.00, '2025-07'),
(30, 'Второй', 'gJHLbmNXoNim8TZaBDpXEiADXaSi+EjaRvVAKZOMS4wSYSZzcfL1IJ4kJ5AT9Bri', '@maria_petrova', 333333333, '88005557575', '1992-08-10', 0, '2025-07-15 12:32:04', 29, 3000.00, 0, 800.00, 3000.00, 0.00, '2025-07'),
(31, 'Третий', 'QLD6wiJnBw3huGw+2eZ1g89Wz1pyzaYnr5aWRch0/8BZAwlj5lV6NO//S6KPjsjD', '@dmitry_sidorov', 444444444, '88004443535', '1988-12-25', 0, '2025-07-15 12:32:04', 30, 2000.00, 0, 0.00, 2000.00, 0.00, '2025-07');

-- --------------------------------------------------------

--
-- Структура таблицы `user_bookmakers`
--

CREATE TABLE `user_bookmakers` (
  `user_id` int(11) NOT NULL,
  `bookmaker_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_bookmakers`
--

INSERT INTO `user_bookmakers` (`user_id`, `bookmaker_id`, `created_at`) VALUES
(24, 3, '2025-07-29 17:52:54'),
(29, 5, '2025-07-29 18:17:09'),
(29, 6, '2025-07-29 18:17:09'),
(29, 8, '2025-07-29 18:17:09'),
(30, 2, '2025-07-29 18:14:58'),
(30, 7, '2025-07-29 18:14:58'),
(30, 11, '2025-07-29 18:14:58'),
(31, 4, '2025-07-29 18:15:04'),
(31, 7, '2025-07-29 18:15:04'),
(31, 12, '2025-07-29 18:15:04');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_name` (`token_name`);

--
-- Индексы таблицы `bookmakers`
--
ALTER TABLE `bookmakers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Индексы таблицы `monthly_payments`
--
ALTER TABLE `monthly_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_month` (`user_id`,`payment_month`);

--
-- Индексы таблицы `referral_earnings`
--
ALTER TABLE `referral_earnings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_earning` (`affiliate_id`,`referral_id`,`level`),
  ADD KEY `referral_id` (`referral_id`);

--
-- Индексы таблицы `referral_settings`
--
ALTER TABLE `referral_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `affiliate_id` (`affiliate_id`);

--
-- Индексы таблицы `user_bookmakers`
--
ALTER TABLE `user_bookmakers`
  ADD PRIMARY KEY (`user_id`,`bookmaker_id`),
  ADD KEY `bookmaker_id` (`bookmaker_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `bookmakers`
--
ALTER TABLE `bookmakers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `monthly_payments`
--
ALTER TABLE `monthly_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `referral_earnings`
--
ALTER TABLE `referral_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT для таблицы `referral_settings`
--
ALTER TABLE `referral_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `monthly_payments`
--
ALTER TABLE `monthly_payments`
  ADD CONSTRAINT `monthly_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `referral_earnings`
--
ALTER TABLE `referral_earnings`
  ADD CONSTRAINT `referral_earnings_ibfk_1` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referral_earnings_ibfk_2` FOREIGN KEY (`referral_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `user_bookmakers`
--
ALTER TABLE `user_bookmakers`
  ADD CONSTRAINT `user_bookmakers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_bookmakers_ibfk_2` FOREIGN KEY (`bookmaker_id`) REFERENCES `bookmakers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
