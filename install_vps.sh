#!/bin/bash

# Скрипт автоматической установки реферальной системы на VPS
# Обновленная версия с исправлениями проблем
# Использование: chmod +x install_vps.sh && sudo ./install_vps.sh

echo "🚀 Установка реферальной системы на VPS (обновленная версия)"
echo "=============================================================="

# Проверяем, что скрипт запущен от root
if [[ $EUID -ne 0 ]]; then
   echo "Этот скрипт должен быть запущен от root (sudo)" 
   exit 1
fi

# Запрашиваем данные у пользователя
echo "Введите данные для настройки:"
read -p "Доменное имя (example.com): " DOMAIN
read -p "Пароль для MySQL пользователя: " -s DB_PASSWORD
echo
read -p "Email для SSL сертификата (оставьте пустым чтобы пропустить): " EMAIL

# Обновляем систему
echo "📦 Обновление системы..."
apt update && apt upgrade -y

# Устанавливаем необходимые пакеты
echo "📦 Установка Apache, MySQL, PHP..."
apt install apache2 mysql-server php libapache2-mod-php php-mysql php-curl php-gd php-mbstring php-xml php-zip unzip wget -y

# Запускаем службы
systemctl start apache2
systemctl enable apache2
systemctl start mysql
systemctl enable mysql

# Включаем необходимые модули Apache
echo "🔧 Настройка Apache модулей..."
a2enmod rewrite
a2enmod ssl
a2enmod headers

# Настраиваем MySQL
echo "🔒 Настройка MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS referral_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "DROP USER IF EXISTS 'referral_user'@'localhost';"
mysql -e "CREATE USER 'referral_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON referral_system.* TO 'referral_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Устанавливаем phpMyAdmin
echo "🌐 Установка phpMyAdmin..."
cd /var/www/html
if [ ! -d "phpmyadmin" ]; then
    wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.tar.gz
    tar -xzf phpMyAdmin-latest-all-languages.tar.gz
    mv phpMyAdmin-*-all-languages phpmyadmin
    rm phpMyAdmin-latest-all-languages.tar.gz
fi

# Настраиваем phpMyAdmin
if [ ! -f "phpmyadmin/config.inc.php" ]; then
    cp phpmyadmin/config.sample.inc.php phpmyadmin/config.inc.php
    BLOWFISH_SECRET=$(openssl rand -base64 32)
    sed -i "s/\$cfg\['blowfish_secret'\] = '';/\$cfg['blowfish_secret'] = '$BLOWFISH_SECRET';/" phpmyadmin/config.inc.php
fi

# Создаем временную директорию для phpMyAdmin
mkdir -p phpmyadmin/tmp
chmod 777 phpmyadmin/tmp

# Создаем директорию проекта
echo "📁 Настройка директории проекта..."
mkdir -p /var/www/html/referral_system
cd /var/www/html/referral_system

# Копируем файлы проекта
echo "📁 Копирование файлов проекта..."
if [ -d "/root/referral_system" ]; then
    cp -r /root/referral_system/* . 2>/dev/null || echo "Файлы скопированы частично"
else
    echo "⚠️  Файлы проекта не найдены в /root/referral_system/"
    echo "   Загрузите их вручную в /var/www/html/referral_system/"
fi

# Создаем правильный файл database.php
echo "🔧 Создание файла подключения к базе данных..."
mkdir -p includes
cat > includes/database.php << EOF
<?php

// Параметры подключения к базе данных
\$db_host = 'localhost';
\$db_name = 'referral_system';
\$db_user = 'referral_user';
\$db_pass = '$DB_PASSWORD';
\$db_charset = 'utf8mb4';

try {
    // Формирование DSN (Data Source Name)
    \$dsn = "mysql:host={\$db_host};dbname={\$db_name};charset={\$db_charset}";
    
    // Опции PDO
    \$options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // Создание соединения с базой данных
    \$pdo = new PDO(\$dsn, \$db_user, \$db_pass, \$options);
    
} catch (PDOException \$e) {
    die("Ошибка подключения к базе данных: " . \$e->getMessage());
}

// Убеждаемся что переменная \$pdo доступна глобально
global \$pdo;
?>
EOF

# Создаем файлы includes если их нет
echo "🔧 Создание файлов includes..."

# Создаем header.php
if [ ! -f "includes/header.php" ]; then
cat > includes/header.php << 'EOF'
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'Реферальная система' ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h2>Реферальная система</h2>
            </div>
            <div class="nav-links">
                <a href="index.php">Главная</a>
                <a href="user_form.php">Добавить пользователя</a>
                <a href="settings.php">Настройки</a>
            </div>
        </div>
    </nav>
EOF
fi

# Создаем footer.php
if [ ! -f "includes/footer.php" ]; then
cat > includes/footer.php << 'EOF'
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Реферальная система. Все права защищены.</p>
        </div>
    </footer>
    
    <script src="js/search.js"></script>
    <script src="js/theme.js"></script>
</body>
</html>
EOF
fi

# Создаем CSS файлы
echo "🎨 Создание CSS файлов..."
mkdir -p css
if [ ! -f "css/style.css" ]; then
cat > css/style.css << 'EOF'
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #f4f4f4;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.navbar {
    background: #007cba;
    color: white;
    padding: 1rem 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.navbar .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-brand h2 {
    margin: 0;
}

.nav-links {
    display: flex;
    gap: 20px;
}

.nav-links a {
    color: white;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.nav-links a:hover {
    background-color: rgba(255,255,255,0.1);
}

.btn {
    display: inline-block;
    background: #007cba;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn:hover {
    background: #005a87;
}

.table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.alert {
    padding: 15px;
    margin: 20px 0;
    border-radius: 4px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.footer {
    background: #333;
    color: white;
    text-align: center;
    padding: 20px 0;
    margin-top: 40px;
}
EOF
fi

# Создаем JavaScript файлы
echo "📜 Создание JavaScript файлов..."
mkdir -p js

# Создаем theme.js
if [ ! -f "js/theme.js" ]; then
cat > js/theme.js << 'EOF'
// Theme switcher functionality
document.addEventListener('DOMContentLoaded', function() {
    // Theme toggle button
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-theme');
            
            // Save theme preference
            const isDark = document.body.classList.contains('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });
    }
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
});
EOF
fi

# Создаем search.js
if [ ! -f "js/search.js" ]; then
cat > js/search.js << 'EOF'
// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('user-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
EOF
fi

# Импортируем базу данных
if [ -f "database.sql" ]; then
    echo "🔧 Импорт базы данных..."
    mysql -u referral_user -p$DB_PASSWORD referral_system < database.sql
else
    echo "⚠️  Файл database.sql не найден. Создайте базу данных вручную."
fi

# Создаем тестовые файлы для диагностики
echo "🔍 Создание тестовых файлов..."

# Тестовый файл для проверки PDO
cat > test_database.php << 'EOF'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

try {
    require_once 'includes/database.php';
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p><strong>Users count:</strong> " . $result['count'] . "</p>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables in database:</h3><ul>";
    foreach($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>
EOF

# Тестовая форма
cat > test_form.php << 'EOF'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/database.php';
require_once 'includes/header.php';
?>

<div class="container">
    <h1>Тестовая форма</h1>
    
    <?php
    if ($_POST) {
        try {
            echo "<div class='alert alert-success'>";
            echo "<h3>✅ Форма работает! Данные получены:</h3>";
            echo "<pre>" . print_r($_POST, true) . "</pre>";
            echo "</div>";
        } catch(Exception $e) {
            echo "<div class='alert alert-error'>❌ Ошибка: " . $e->getMessage() . "</div>";
        }
    }
    ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Имя:</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <button type="submit" class="btn">Отправить</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
EOF

# Настраиваем права доступа
echo "🔐 Настройка прав доступа..."
chown -R www-data:www-data /var/www/html/referral_system
chown -R www-data:www-data /var/www/html/phpmyadmin
find /var/www/html/referral_system -type d -exec chmod 755 {} \;
find /var/www/html/referral_system -type f -exec chmod 644 {} \;
find /var/www/html/phpmyadmin -type d -exec chmod 755 {} \;
find /var/www/html/phpmyadmin -type f -exec chmod 644 {} \;
mkdir -p /var/www/html/referral_system/uploads
chmod 777 /var/www/html/referral_system/uploads

# Настраиваем виртуальный хост
echo "🌍 Настройка виртуального хоста..."
cat > /etc/apache2/sites-available/referral_system.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot /var/www/html/referral_system
    
    <Directory /var/www/html/referral_system>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>
    
    # phpMyAdmin
    Alias /phpmyadmin /var/www/html/phpmyadmin
    <Directory /var/www/html/phpmyadmin>
        Options FollowSymLinks
        DirectoryIndex index.php
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/referral_system_error.log
    CustomLog \${APACHE_LOG_DIR}/referral_system_access.log combined
</VirtualHost>
EOF

# Создаем конфигурацию phpMyAdmin
cat > /etc/apache2/conf-available/phpmyadmin.conf << 'EOF'
# phpMyAdmin configuration
Alias /phpmyadmin /var/www/html/phpmyadmin

<Directory /var/www/html/phpmyadmin>
    Options SymLinksIfOwnerMatch
    DirectoryIndex index.php
    AllowOverride All
    Require all granted

    <Files "config.inc.php">
        Require all denied
    </Files>
</Directory>

<Directory /var/www/html/phpmyadmin/libraries>
    Require all denied
</Directory>
EOF

# Включаем сайт и конфигурации
a2ensite referral_system.conf
a2enconf phpmyadmin
systemctl reload apache2

# Настраиваем файрвол
echo "🛡️ Настройка файрвола..."
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# Устанавливаем SSL сертификат
if [ ! -z "$EMAIL" ]; then
    echo "🔐 Установка SSL сертификата..."
    apt install certbot python3-certbot-apache -y
    certbot --apache -d $DOMAIN -d www.$DOMAIN --email $EMAIL --agree-tos --non-interactive
    
    # Добавляем phpMyAdmin в SSL конфигурацию
    if [ -f "/etc/apache2/sites-available/referral_system-le-ssl.conf" ]; then
        if ! grep -q "phpmyadmin" /etc/apache2/sites-available/referral_system-le-ssl.conf; then
            sed -i '/<\/VirtualHost>/i\    # phpMyAdmin\
    Alias /phpmyadmin /var/www/html/phpmyadmin\
    <Directory /var/www/html/phpmyadmin>\
        Options FollowSymLinks\
        DirectoryIndex index.php\
        AllowOverride All\
        Require all granted\
    </Directory>' /etc/apache2/sites-available/referral_system-le-ssl.conf
        fi
    fi
    
    # Настраиваем автоматическое обновление
    echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
fi

# Создаем скрипт для резервного копирования
echo "💾 Настройка резервного копирования..."
mkdir -p /var/backups
cat > /usr/local/bin/backup_referral.sh << EOF
#!/bin/bash
DATE=\$(date +%Y%m%d_%H%M%S)
mysqldump -u referral_user -p'$DB_PASSWORD' referral_system > /var/backups/referral_\$DATE.sql
find /var/backups -name "referral_*.sql" -mtime +7 -delete
EOF

chmod +x /usr/local/bin/backup_referral.sh

# Добавляем в cron ежедневное резервное копирование
echo "0 2 * * * /usr/local/bin/backup_referral.sh" | crontab -

# Проверяем конфигурацию Apache
echo "🔧 Проверка конфигурации Apache..."
apache2ctl configtest

# Перезапускаем Apache
systemctl restart apache2

# Финальная проверка
echo "🔍 Проверка установки..."
sleep 2

echo ""
echo "✅ Установка завершена!"
echo "========================"
echo "🌐 Сайт доступен по адресу: http://$DOMAIN"
if [ ! -z "$EMAIL" ]; then
    echo "🔒 HTTPS: https://$DOMAIN"
fi
echo "🗄️  phpMyAdmin: http://$DOMAIN/phpmyadmin"
echo "👤 Логин для базы данных: referral_user"
echo "🔑 Пароль для базы данных: $DB_PASSWORD"
echo ""
echo "🧪 Тестовые страницы:"
echo "   - $DOMAIN/test_database.php - проверка БД"
echo "   - $DOMAIN/test_form.php - тестовая форма"
echo ""
echo "📊 Логи и управление:"
echo "   - Логи Apache: /var/log/apache2/referral_system_error.log"
echo "   - Резервные копии: /var/backups/"
echo "   - Скрипт бэкапа: /usr/local/bin/backup_referral.sh"
echo ""
echo "🚀 Готово к использованию!"
echo ""
echo "📋 Проверьте работу сайта:"
echo "1. Откройте http://$DOMAIN"
echo "2. Проверьте phpMyAdmin"
echo "3. Протестируйте добавление пользователей"
echo ""
echo "Удачи! 🎉" 