-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Июл 15 2025 г., 18:32
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
(42, 24, 29, 1, 2500.00, '2025-07-15 13:10:58', '2025-07-15 13:15:31'),
(43, 29, 30, 1, 1250.00, '2025-07-15 13:10:58', '2025-07-15 13:15:53'),
(44, 24, 30, 2, 625.00, '2025-07-15 13:10:58', '2025-07-15 13:15:53'),
(45, 30, 31, 1, 500.00, '2025-07-15 13:10:58', '2025-07-15 13:10:58'),
(46, 29, 31, 2, 250.00, '2025-07-15 13:10:58', '2025-07-15 13:10:58'),
(47, 24, 31, 3, 100.00, '2025-07-15 13:10:58', '2025-07-15 13:10:58');

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
(7, 'level_1_percent', 50.00, 'Процент для рефералов 1 уровня', '2025-07-15 12:35:48', '2025-07-15 12:35:48'),
(8, 'level_2_percent', 25.00, 'Процент для рефералов 2 уровня', '2025-07-15 12:35:48', '2025-07-15 12:35:48'),
(9, 'level_3_percent', 10.00, 'Процент для рефералов 3 уровня', '2025-07-15 12:35:48', '2025-07-15 12:35:48');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `bank_card` text NOT NULL,
  `telegram_username` varchar(255) NOT NULL,
  `telegram_id` bigint(20) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `birth_date` date NOT NULL,
  `is_affiliate` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `affiliate_id` int(11) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплаченно в рублях',
  `referral_count` int(11) DEFAULT 0 COMMENT 'Количество рефералов',
  `paid_for_referrals` decimal(10,2) DEFAULT 0.00 COMMENT 'Выплаченно за рефералов'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `full_name`, `bank_card`, `telegram_username`, `telegram_id`, `phone_number`, `birth_date`, `is_affiliate`, `created_at`, `affiliate_id`, `paid_amount`, `referral_count`, `paid_for_referrals`) VALUES
(24, 'Сергей Партнеров', 'IdVGqsBa50mnFVdNMJoLgKDehTBxLbUYJqFcgyOZUEmoFB2/Wvr3H7fk521X8DJ7', '@sergey_partner', 430892673, '88005553535', '1985-03-15', 1, '2025-07-15 12:30:14', NULL, 10000.00, 1, 3225.00),
(29, 'Андрей Иванов', '1OMcUmADyg47fJtZYAiXj3V1wU+eTbuOtmhkwIXAKZe0kN1raR/Ib17T0SuS1JDy', '@andrey_ivanov', 222222222, '88005554545', '1990-05-20', 0, '2025-07-15 12:32:04', 24, 5000.00, 0, 1500.00),
(30, 'Мария Петрова', '7o3i4XpuqEeqbg7eJtnKNstzvVJXNE7mx2sr9qcZRu4A6DtapEK+EFZGpPuOKcYR', '@maria_petrova', 333333333, '88005557575', '1992-08-10', 0, '2025-07-15 12:32:04', 29, 2500.00, 0, 500.00),
(31, 'Дмитрий Сидоров', 'X9M+5nX40P3aFhVeaNhh3XHKryw9oeWTS+JxruljZT5pd59bVzsEw3y7WMjY981D', '@dmitry_sidorov', 444444444, '88004443535', '1988-12-25', 0, '2025-07-15 12:32:04', 30, 1000.00, 0, 0.00),
(32, 'Петр Петров', 'ls8+97Y9D3MKNfIEnrwULGzzJjydazQ9ARbV5XJ0azPwfw6stLuAu7GrePDoys7Q', '@petr_petrov', 987654321, '89057654321', '1985-05-15', 1, '2025-07-15 13:07:44', NULL, 0.00, 0, 0.00);

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
(24, 3, '2025-07-15 13:50:22'),
(29, 5, '2025-07-15 13:15:31'),
(29, 6, '2025-07-15 13:15:31'),
(29, 8, '2025-07-15 13:15:31'),
(30, 2, '2025-07-15 13:15:53'),
(30, 7, '2025-07-15 13:15:53'),
(30, 11, '2025-07-15 13:15:53'),
(31, 4, '2025-07-15 13:00:15'),
(31, 7, '2025-07-15 13:00:15'),
(31, 12, '2025-07-15 13:00:15'),
(32, 3, '2025-07-15 13:33:23'),
(32, 4, '2025-07-15 13:33:23');

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
-- AUTO_INCREMENT для таблицы `referral_earnings`
--
ALTER TABLE `referral_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT для таблицы `referral_settings`
--
ALTER TABLE `referral_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

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