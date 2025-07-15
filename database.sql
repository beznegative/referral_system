-- Создание базы данных
CREATE DATABASE IF NOT EXISTS referral_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Использование базы данных
USE referral_system;

-- Изменение структуры таблицы users
ALTER TABLE users
    MODIFY COLUMN birth_date DATE NOT NULL;

-- Создание таблицы пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    bank_card TEXT NOT NULL,
    telegram_username VARCHAR(255) NOT NULL,
    telegram_id BIGINT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    birth_date DATE NOT NULL,
    is_affiliate BOOLEAN DEFAULT 0,
    affiliate_id INT DEFAULT NULL,
    passport_photo MEDIUMBLOB NOT NULL,
    passport_photo_type VARCHAR(50) NOT NULL,
    address_photo MEDIUMBLOB NOT NULL,
    address_photo_type VARCHAR(50) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    paid_for_referrals DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (affiliate_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы букмекерских контор
CREATE TABLE IF NOT EXISTS bookmakers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы связи пользователей и букмекерских контор
CREATE TABLE IF NOT EXISTS user_bookmakers (
    user_id INT NOT NULL,
    bookmaker_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, bookmaker_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bookmaker_id) REFERENCES bookmakers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы настроек реферальной системы
CREATE TABLE IF NOT EXISTS referral_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(50) NOT NULL UNIQUE,
    setting_value DECIMAL(5,2) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка настроек по умолчанию для реферальной системы
INSERT INTO referral_settings (setting_name, setting_value, description) VALUES 
('level_1_percent', 50.00, 'Процент для рефералов 1 уровня'),
('level_2_percent', 25.00, 'Процент для рефералов 2 уровня'),
('level_3_percent', 10.00, 'Процент для рефералов 3 уровня')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Вставка легальных букмекерских контор
INSERT INTO bookmakers (name, code) VALUES 
('1xСтавка', '1xstavka'),
('Betboom', 'betboom'),
('Winline', 'winline'),
('Фонбет', 'fonbet'),
('Лига Ставок', 'ligastavok'),
('Марафон', 'marathon'),
('Пари', 'pari'),
('Мелбет', 'melbet'),
('Бетсити', 'betcity'),
('Зенит', 'zenit'),
('Pin Up', 'pinup'),
('Олимп', 'olimp'),
('Tennisi', 'tennisi'),
('БЕТМАСТЕР', 'betmaster'),
('Астрабет', 'astrabet'); 