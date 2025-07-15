# 📋 Инструкция по развертыванию реферальной системы на VPS

## 🚀 Требования

- VPS сервер с Ubuntu 20.04+ / CentOS 8+
- Доступ к серверу по SSH
- Доменное имя или IP-адрес сервера

## 📦 Шаг 1: Установка необходимого ПО

### На Ubuntu/Debian:

```bash
# Обновляем систему
sudo apt update && sudo apt upgrade -y

# Устанавливаем Apache, MySQL, PHP
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-curl php-gd php-mbstring php-xml php-zip unzip -y

# Запускаем и добавляем в автозагрузку
sudo systemctl start apache2
sudo systemctl enable apache2
sudo systemctl start mysql
sudo systemctl enable mysql
```

### На CentOS/RHEL:

```bash
# Обновляем систему
sudo yum update -y

# Устанавливаем Apache, MySQL, PHP
sudo yum install httpd mysql-server php php-mysqlnd php-curl php-gd php-mbstring php-xml php-zip unzip -y

# Запускаем и добавляем в автозагрузку
sudo systemctl start httpd
sudo systemctl enable httpd
sudo systemctl start mysqld
sudo systemctl enable mysqld
```

## 🔒 Шаг 2: Настройка MySQL

```bash
# Запускаем скрипт безопасности MySQL
sudo mysql_secure_installation

# Отвечаем на вопросы:
# Set root password? [Y/n] Y
# Remove anonymous users? [Y/n] Y
# Disallow root login remotely? [Y/n] Y
# Remove test database and access to it? [Y/n] Y
# Reload privilege tables now? [Y/n] Y

# Входим в MySQL
sudo mysql -u root -p

# Создаем базу данных и пользователя
CREATE DATABASE referral_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'referral_user'@'localhost' IDENTIFIED BY 'ваш_пароль_здесь';
GRANT ALL PRIVILEGES ON referral_system.* TO 'referral_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 🌐 Шаг 3: Установка phpMyAdmin

```bash
# Ubuntu/Debian
sudo apt install phpmyadmin -y

# CentOS/RHEL (устанавливаем через wget)
cd /var/www/html
sudo wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.tar.gz
sudo tar -xzf phpMyAdmin-latest-all-languages.tar.gz
sudo mv phpMyAdmin-*-all-languages phpmyadmin
sudo chown -R apache:apache phpmyadmin/
```

### Настройка phpMyAdmin:

```bash
# Создаем конфигурационный файл
sudo cp /var/www/html/phpmyadmin/config.sample.inc.php /var/www/html/phpmyadmin/config.inc.php

# Редактируем конфигурацию
sudo nano /var/www/html/phpmyadmin/config.inc.php

# Находим и изменяем:
$cfg['blowfish_secret'] = 'сгенерированный_секретный_ключ_32_символа';
```

## 📁 Шаг 4: Загрузка проекта

```bash
# Переходим в директорию веб-сервера
cd /var/www/html

# Создаем директорию для проекта
sudo mkdir referral_system
cd referral_system

# Загружаем проект (замените на ваш способ загрузки)
# Вариант 1: Если проект на GitHub
# sudo git clone https://github.com/your-username/referral_system.git .

# Вариант 2: Если загружаете через SCP/SFTP
# scp -r /path/to/your/project/* user@your-server:/var/www/html/referral_system/

# Вариант 3: Если загружаете через wget (архив)
# sudo wget your-project-archive.zip
# sudo unzip your-project-archive.zip
# sudo mv referral_system/* .
# sudo rmdir referral_system

# Устанавливаем права доступа
sudo chown -R www-data:www-data /var/www/html/referral_system
sudo chmod -R 755 /var/www/html/referral_system
sudo chmod -R 777 /var/www/html/referral_system/uploads
```

## 🔧 Шаг 5: Настройка базы данных

```bash
# Импортируем дамп базы данных
mysql -u referral_user -p referral_system < /var/www/html/referral_system/database.sql

# Проверяем импорт
mysql -u referral_user -p referral_system -e "SHOW TABLES;"
```

## ⚙️ Шаг 6: Настройка проекта

```bash
# Редактируем файл подключения к базе данных
sudo nano /var/www/html/referral_system/includes/database.php

# Изменяем параметры подключения:
$db_host = 'localhost';
$db_name = 'referral_system';
$db_user = 'referral_user';
$db_pass = 'ваш_пароль_здесь';
```

## 🌍 Шаг 7: Настройка Apache

```bash
# Создаем виртуальный хост
sudo nano /etc/apache2/sites-available/referral_system.conf

# Добавляем конфигурацию:
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/html/referral_system
    
    <Directory /var/www/html/referral_system>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/referral_system_error.log
    CustomLog ${APACHE_LOG_DIR}/referral_system_access.log combined
</VirtualHost>

# Включаем сайт и перезагружаем Apache
sudo a2ensite referral_system.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

## 🔐 Шаг 8: Настройка SSL (опционально, но рекомендуется)

```bash
# Устанавливаем Certbot
sudo apt install certbot python3-certbot-apache -y

# Получаем SSL сертификат
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# Настраиваем автоматическое обновление
sudo crontab -e
# Добавляем строку:
0 12 * * * /usr/bin/certbot renew --quiet
```

## 🛡️ Шаг 9: Настройка файрвола

```bash
# Ubuntu/Debian (UFW)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# CentOS/RHEL (firewalld)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --reload
```

## 🎯 Шаг 10: Проверка работы

1. **Проверка веб-сайта:**
   - Откройте браузер и перейдите на `http://your-domain.com`
   - Должна отобразиться главная страница реферальной системы

2. **Проверка phpMyAdmin:**
   - Перейдите на `http://your-domain.com/phpmyadmin`
   - Войдите с данными: `referral_user` и вашим паролем

3. **Проверка функционала:**
   - Попробуйте добавить нового пользователя
   - Проверьте работу реферальной системы

## 📊 Шаг 11: Мониторинг и обслуживание

```bash
# Проверка логов Apache
sudo tail -f /var/log/apache2/referral_system_error.log

# Проверка логов MySQL
sudo tail -f /var/log/mysql/error.log

# Резервное копирование базы данных
mysqldump -u referral_user -p referral_system > backup_$(date +%Y%m%d_%H%M%S).sql

# Автоматическое резервное копирование (добавить в cron)
sudo crontab -e
# Добавить строку для ежедневного бэкапа в 2:00
0 2 * * * mysqldump -u referral_user -p'ваш_пароль' referral_system > /var/backups/referral_$(date +\%Y\%m\%d).sql
```

## 🔧 Возможные проблемы и решения

### 1. Ошибка "Permission denied"
```bash
sudo chown -R www-data:www-data /var/www/html/referral_system
sudo chmod -R 755 /var/www/html/referral_system
```

### 2. Ошибка подключения к базе данных
- Проверьте настройки в `includes/database.php`
- Убедитесь, что MySQL запущен: `sudo systemctl status mysql`

### 3. Ошибка "Forbidden"
```bash
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### 4. Проблемы с загрузкой файлов
```bash
sudo chmod 777 /var/www/html/referral_system/uploads
```

## 📝 Заключение

После выполнения всех шагов ваша реферальная система будет работать на VPS сервере с phpMyAdmin. Не забудьте:

- Регулярно обновлять систему
- Делать резервные копии
- Мониторить логи
- Обновлять пароли

Удачи в использовании! 🚀 