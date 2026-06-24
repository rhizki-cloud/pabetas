#!/usr/bin/env bash
set -e
APP_DIR=${APP_DIR:-/www/wwwroot/pabetas}
DB_NAME=${DB_NAME:-pabetas}
DB_USER=${DB_USER:-pabetas_user}
DB_PASS=${DB_PASS:-$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 16)}
DOMAIN=${DOMAIN:-pabetas.sch.id}

echo "=== PABETAS One Click VPS Installer ==="
echo "Target folder: $APP_DIR"
echo "Domain: $DOMAIN"

apt update
apt install -y apache2 mysql-server php php-mysql php-mbstring php-xml php-curl php-zip php-gd unzip curl composer
mkdir -p "$APP_DIR"
rsync -a --exclude deploy ./ "$APP_DIR/"
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"

mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"
mysql "$DB_NAME" < "$APP_DIR/database/pabetas_advanced.sql"

cat > "$APP_DIR/app/config.php" <<PHP
<?php
define('APP_NAME','PABETAS');
define('APP_ENV','production');
define('APP_DEBUG',false);
define('APP_URL','');
define('DB_HOST','127.0.0.1');
define('DB_NAME','$DB_NAME');
define('DB_USER','$DB_USER');
define('DB_PASS','$DB_PASS');
define('DB_CHARSET','utf8mb4');
define('SCHOOL_NAME','SD Contoh Nusantara');
define('SCHOOL_LOGO','assets/img/logo-pabetas.svg');
define('SESSION_TIMEOUT',2700);
PHP

cat > /etc/apache2/sites-available/pabetas.conf <<APACHE
<VirtualHost *:80>
    ServerName $DOMAIN
    DocumentRoot $APP_DIR/public
    <Directory $APP_DIR/public>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \\${APACHE_LOG_DIR}/pabetas_error.log
    CustomLog \\${APACHE_LOG_DIR}/pabetas_access.log combined
</VirtualHost>
APACHE

a2ensite pabetas.conf
a2enmod rewrite
systemctl reload apache2
cd "$APP_DIR" && composer install --no-dev --optimize-autoloader || true

echo "=== Instalasi selesai ==="
echo "DB_NAME=$DB_NAME"
echo "DB_USER=$DB_USER"
echo "DB_PASS=$DB_PASS"
echo "Login demo: guru/guru123 dan siswa/siswa123"
echo "Aktifkan SSL dari aaPanel atau jalankan certbot sesuai domain."
