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

## ✅ Основные проблемы решены - тестируем сайт

### 1. Исправим тестовый файл для корректного отображения таблиц:
```bash
sudo tee /var/www/html/referral_system/test_tables.php > /dev/null <<'EOF'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/database.php';

echo "<h1>Database Tables Test</h1>";

try {
    // Проверяем таблицы правильным способом
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables in database:</h3><ul>";
    foreach($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // Проверяем содержимое таблицы users
    $stmt = $pdo->query("SELECT id, full_name, phone_number, created_at FROM users LIMIT 3");
    $users = $stmt->fetchAll();
    
    echo "<h3>Sample users:</h3>";
    if ($users) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Phone</th><th>Created</th></tr>";
        foreach($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['phone_number']) . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch(Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
EOF

sudo chown www-data:www-data /var/www/html/referral_system/test_tables.php
```

### 2. Теперь проверим работает ли основной сайт:

**Откройте эти страницы в браузере:**

1. **https://beznegativaplus.ru/** - главная страница
2. **https://beznegativaplus.ru/user_form.php** - форма добавления пользователя  
3. **https://beznegativaplus.ru/user.php** - список пользователей
4. **https://beznegativaplus.ru/settings.php** - настройки

### 3. Проверим работают ли CSS и JS файлы:
```bash
# Проверяем доступность статических файлов
curl -I https://beznegativaplus.ru/css/style.css
curl -I https://beznegativaplus.ru/js/theme.js
curl -I https://beznegativaplus.ru/js/search.js
```

### 4. Если CSS/JS файлы не загружаются, создадим их:
```bash
# Проверяем структуру директорий
ls -la /var/www/html/referral_system/

# Создаем недостающие директории и файлы
sudo mkdir -p /var/www/html/referral_system/css
sudo mkdir -p /var/www/html/referral_system/js

# Копируем базовые стили и скрипты из предыдущих команд
sudo cp /var/www/html/referral_system/css/style.css /var/www/html/referral_system/css/style.css.backup 2>/dev/null || true
sudo cp /var/www/html/referral_system/js/theme.js /var/www/html/referral_system/js/theme.js.backup 2>/dev/null || true
```

### 5. Проверим работу формы добавления пользователя:
```bash
<code_block_to_apply_changes_from>
```

## 🎯 Тестирование после исправлений:

### Проверьте эти страницы:

1. **https://beznegativaplus.ru/test_tables.php** - проверка таблиц БД
2. **https://beznegativaplus.ru/test_form_working.php** - работающая форма
3. **https://beznegativaplus.ru/** - главная страница
4. **https://beznegativaplus.ru/user_form.php** - оригинальная форма

### В браузере (F12 -> Console) проверьте:
- Есть ли ошибки JavaScript
- Загружаются ли CSS файлы (вкладка Network)

### Если главная страница работает, но нет стилей:
```bash
# Проверяем загружается ли CSS
curl -v https://beznegativaplus.ru/css/style.css
```

Теперь большинство проблем должно быть решено! База данных работает отлично. Сообщите результаты тестирования страниц! 🚀 