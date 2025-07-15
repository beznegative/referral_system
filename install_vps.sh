#!/bin/bash

# Скрипт автоматической установки реферальной системы на VPS
# Использование: chmod +x install_vps.sh && sudo ./install_vps.sh

echo "🚀 Установка реферальной системы на VPS"
echo "========================================"

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
read -p "Email для SSL сертификата: " EMAIL

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

# Настраиваем MySQL
echo "🔒 Настройка MySQL..."
mysql -e "CREATE DATABASE referral_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'referral_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON referral_system.* TO 'referral_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Устанавливаем phpMyAdmin
echo "🌐 Установка phpMyAdmin..."
cd /var/www/html
wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.tar.gz
tar -xzf phpMyAdmin-latest-all-languages.tar.gz
mv phpMyAdmin-*-all-languages phpmyadmin
rm phpMyAdmin-latest-all-languages.tar.gz

# Настраиваем phpMyAdmin
cp phpmyadmin/config.sample.inc.php phpmyadmin/config.inc.php
BLOWFISH_SECRET=$(openssl rand -base64 32)
sed -i "s/\$cfg\['blowfish_secret'\] = '';/\$cfg['blowfish_secret'] = '$BLOWFISH_SECRET';/" phpmyadmin/config.inc.php

# Создаем директорию проекта
mkdir -p /var/www/html/referral_system
cd /var/www/html/referral_system

# Копируем файлы проекта (предполагается, что они в текущей директории)
echo "📁 Копирование файлов проекта..."
# Здесь должны быть файлы проекта
# Если запускаете не из директории проекта, измените путь
cp -r /path/to/project/* . 2>/dev/null || echo "Файлы проекта не найдены. Загрузите их вручную в /var/www/html/referral_system/"

# Импортируем базу данных
if [ -f "database.sql" ]; then
    echo "🔧 Импорт базы данных..."
    mysql -u referral_user -p$DB_PASSWORD referral_system < database.sql
fi

# Настраиваем права доступа
echo "🔐 Настройка прав доступа..."
chown -R www-data:www-data /var/www/html/referral_system
chmod -R 755 /var/www/html/referral_system
mkdir -p /var/www/html/referral_system/uploads
chmod -R 777 /var/www/html/referral_system/uploads

# Обновляем конфигурацию базы данных
if [ -f "includes/database.php" ]; then
    sed -i "s/\$db_user = 'root';/\$db_user = 'referral_user';/" includes/database.php
    sed -i "s/\$db_pass = '';/\$db_pass = '$DB_PASSWORD';/" includes/database.php
fi

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
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/referral_system_error.log
    CustomLog \${APACHE_LOG_DIR}/referral_system_access.log combined
</VirtualHost>
EOF

# Включаем сайт
a2ensite referral_system.conf
a2enmod rewrite
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

# Перезапускаем Apache
systemctl restart apache2

echo "✅ Установка завершена!"
echo "========================"
echo "Сайт доступен по адресу: http://$DOMAIN"
echo "phpMyAdmin: http://$DOMAIN/phpmyadmin"
echo "Логин для базы данных: referral_user"
echo "Пароль для базы данных: $DB_PASSWORD"
echo ""
echo "Логи Apache: /var/log/apache2/referral_system_error.log"
echo "Резервные копии: /var/backups/"
echo ""
echo "Для проверки работы перейдите на http://$DOMAIN"
echo "🚀 Удачи в использовании!" 