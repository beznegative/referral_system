-- Тестовые данные для проверки 3-уровневой реферальной системы
-- Выполнить после создания основных таблиц

-- Убедимся что настройки реферальной системы существуют
INSERT INTO referral_settings (setting_name, setting_value, description) VALUES 
('level_1_percent', 50.00, 'Процент для рефералов 1 уровня'),
('level_2_percent', 25.00, 'Процент для рефералов 2 уровня'),
('level_3_percent', 10.00, 'Процент для рефералов 3 уровня')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    description = VALUES(description);

-- Очистка тестовых данных (если нужно)
-- DELETE FROM users WHERE telegram_username LIKE 'test_%';

-- Создаем партнера (главный реферер)
INSERT INTO users (
    full_name, 
    bank_card, 
    telegram_username, 
    telegram_id, 
    phone_number, 
    birth_date, 
    is_affiliate, 
    affiliate_id, 
    passport_photo, 
    passport_photo_type, 
    address_photo, 
    address_photo_type, 
    paid_amount, 
    paid_for_referrals
) VALUES (
    'Иванов Иван Иванович (Партнер)', 
    'encrypted_card_data_1234567890123456', 
    'test_partner_ivan', 
    123456789, 
    '+7(999)123-45-67', 
    '1985-05-15', 
    1, 
    NULL, 
    'fake_passport_photo_data', 
    'image/jpeg', 
    'fake_address_photo_data', 
    'image/jpeg', 
    5000.00, 
    0.00
);

-- Получаем ID партнера
SET @partner_id = LAST_INSERT_ID();

-- Создаем рефералов 1 уровня
INSERT INTO users (
    full_name, 
    bank_card, 
    telegram_username, 
    telegram_id, 
    phone_number, 
    birth_date, 
    is_affiliate, 
    affiliate_id, 
    passport_photo, 
    passport_photo_type, 
    address_photo, 
    address_photo_type, 
    paid_amount, 
    paid_for_referrals
) VALUES 
-- Реферал 1 уровня #1
(
    'Петров Петр Петрович (Реферал 1 уровня)', 
    'encrypted_card_data_1111222233334444', 
    'test_referral_1_petr', 
    123456790, 
    '+7(999)111-22-33', 
    '1990-03-10', 
    0, 
    @partner_id, 
    'fake_passport_photo_data', 
    'image/jpeg', 
    'fake_address_photo_data', 
    'image/jpeg', 
    2000.00, 
    0.00
),
-- Реферал 1 уровня #2  
(
    'Сидоров Сидор Сидорович (Реферал 1 уровня)', 
    'encrypted_card_data_2222333344445555', 
    'test_referral_1_sidor', 
    123456791, 
    '+7(999)222-33-44', 
    '1988-07-20', 
    0, 
    @partner_id, 
    'fake_passport_photo_data', 
    'image/jpeg', 
    'fake_address_photo_data', 
    'image/jpeg', 
    3000.00, 
    0.00
);

-- Получаем ID рефералов 1 уровня
SET @referral_1_id_1 = LAST_INSERT_ID() - 1;
SET @referral_1_id_2 = LAST_INSERT_ID();

-- Создаем рефералов 2 уровня
INSERT INTO users (
    full_name, 
    bank_card, 
    telegram_username, 
    telegram_id, 
    phone_number, 
    birth_date, 
    is_affiliate, 
    affiliate_id, 
    passport_photo, 
    passport_photo_type, 
    address_photo, 
    address_photo_type, 
    paid_amount, 
    paid_for_referrals
) VALUES 
-- Реферал 2 уровня от первого реферала 1 уровня
(
    'Козлов Козел Козлович (Реферал 2 уровня)', 
    'encrypted_card_data_3333444455556666', 
    'test_referral_2_kozel', 
    123456792, 
    '+7(999)333-44-55', 
    '1992-11-05', 
    0, 
    @referral_1_id_1, 
    'fake_passport_photo_data', 
    'image/jpeg', 
    'fake_address_photo_data', 
    'image/jpeg', 
    1500.00, 
    0.00
),
-- Реферал 2 уровня от второго реферала 1 уровня
(
    'Васильев Василий Васильевич (Реферал 2 уровня)', 
    'encrypted_card_data_4444555566667777', 
    'test_referral_2_vasiliy', 
    123456793, 
    '+7(999)444-55-66', 
    '1993-12-25', 
    0, 
    @referral_1_id_2, 
    'fake_passport_photo_data', 
    'image/jpeg', 
    'fake_address_photo_data', 
    'image/jpeg', 
    800.00, 
    0.00
);

-- Получаем ID рефералов 2 уровня
SET @referral_2_id_1 = LAST_INSERT_ID() - 1;
SET @referral_2_id_2 = LAST_INSERT_ID();

-- Создаем рефералов 3 уровня
INSERT INTO users (
    full_name, 
    bank_card, 
    telegram_username, 
    telegram_id, 
    phone_number, 
    birth_date, 
    is_affiliate, 
    affiliate_id, 
    passport_photo, 
    passport_photo_type, 
    address_photo, 
    address_photo_type, 
    paid_amount, 
    paid_for_referrals
) VALUES 
-- Реферал 3 уровня от первого реферала 2 уровня
(
    'Николаев Николай Николаевич (Реферал 3 уровня)', 
    'encrypted_card_data_5555666677778888', 
    'test_referral_3_nikolay', 
    123456794, 
    '+7(999)555-66-77', 
    '1995-02-14', 
    0, 
    @referral_2_id_1, 
    'fake_passport_photo_data', 
    'image/jpeg', 
    'fake_address_photo_data', 
    'image/jpeg', 
    600.00, 
    0.00
),
-- Реферал 3 уровня от второго реферала 2 уровня
(
    'Морозов Мороз Морозович (Реферал 3 уровня)', 
    'encrypted_card_data_6666777788889999', 
    'test_referral_3_moroz', 
    123456795, 
    '+7(999)666-77-88', 
    '1996-06-30', 
    0, 
    @referral_2_id_2, 
    'fake_passport_photo_data', 
    'image/jpeg', 
    'fake_address_photo_data', 
    'image/jpeg', 
    1000.00, 
    0.00
);

-- Добавляем связи с букмекерскими конторами для тестовых пользователей
INSERT INTO user_bookmakers (user_id, bookmaker_id) 
SELECT u.id, b.id 
FROM users u 
CROSS JOIN bookmakers b 
WHERE u.telegram_username LIKE 'test_%' 
  AND b.code IN ('1xstavka', 'fonbet', 'betboom')
LIMIT 20;

-- Проверочный запрос: показать структуру рефералов
SELECT 
    u.id,
    u.full_name,
    u.telegram_username,
    u.paid_amount,
    u.is_affiliate,
    u.affiliate_id,
    CASE 
        WHEN u.is_affiliate = 1 THEN 'Партнер'
        WHEN u.affiliate_id = @partner_id THEN '1 уровень'
        WHEN u.affiliate_id IN (@referral_1_id_1, @referral_1_id_2) THEN '2 уровень'
        WHEN u.affiliate_id IN (@referral_2_id_1, @referral_2_id_2) THEN '3 уровень'
        ELSE 'Неизвестный уровень'
    END as level_description
FROM users u 
WHERE u.telegram_username LIKE 'test_%' 
ORDER BY u.id;

-- Расчет ожидаемых выплат для партнера:
-- 1 уровень: (2000 + 3000) * 50% = 2500₽
-- 2 уровень: (1500 + 800) * 25% = 575₽  
-- 3 уровень: (600 + 1000) * 10% = 160₽
-- ИТОГО: 3235₽

SELECT 
    'Ожидаемые выплаты для партнера' as description,
    (2000 + 3000) * 0.50 as level_1_earnings,
    (1500 + 800) * 0.25 as level_2_earnings,
    (600 + 1000) * 0.10 as level_3_earnings,
    ((2000 + 3000) * 0.50) + ((1500 + 800) * 0.25) + ((600 + 1000) * 0.10) as total_earnings; 