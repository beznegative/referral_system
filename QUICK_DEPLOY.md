# ⚡ Быстрое развертывание реферальной системы

## 🚀 Краткая шпаргалка для опытных пользователей

```bash
# 1. Установка LAMP (Ubuntu)
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-curl php-gd php-mbstring php-xml php-zip phpmyadmin -y

# 2. Настройка MySQL
sudo mysql_secure_installation
sudo mysql -u root -p
CREATE DATABASE referral_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'referral_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON referral_system.* TO 'referral_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 3. Загрузка проекта
cd /var/www/html
sudo mkdir referral_system
cd referral_system
# Загрузите файлы проекта здесь

# 4. Настройка прав доступа
sudo chown -R www-data:www-data /var/www/html/referral_system
sudo chmod -R 755 /var/www/html/referral_system
sudo chmod -R 777 /var/www/html/referral_system/uploads

# 5. Импорт базы данных
mysql -u referral_user -p referral_system < database.sql

# 6. Настройка database.php
sudo nano includes/database.php
# Измените параметры подключения

# 7. Настройка Apache
sudo a2enmod rewrite
sudo systemctl reload apache2

# 8. Проверка
# Откройте http://your-domain.com
```

## 🔧 Настройка database.php

```php
<?php
$db_host = 'localhost';
$db_name = 'referral_system';
$db_user = 'referral_user';
$db_pass = 'your_password';
$db_charset = 'utf8mb4';
```

## 🌐 Доступ к phpMyAdmin

- URL: `http://your-domain.com/phpmyadmin`
- Логин: `referral_user`
- Пароль: `your_password`

## 📱 Проверка работы

1. Главная страница: `http://your-domain.com`
2. Форма добавления: `http://your-domain.com/user_form.php`
3. Список пользователей: `http://your-domain.com/user.php`

## 🛡️ Безопасность

```bash
# Настройка файрвола
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# SSL (опционально)
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d your-domain.com
``` 