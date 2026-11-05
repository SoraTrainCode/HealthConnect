# syntax=docker/dockerfile:1
# Giữ xuống dòng LF để heredoc chạy trên Linux khi checkout từ Windows.
FROM php:8.2-apache-bookworm

# CA của proxy chỉ được mount lúc build, không được lưu vào image.
RUN --mount=type=secret,id=ca_bundle,mode=0444 \
    set -eu; \
    sed -i 's|http://deb.debian.org|https://deb.debian.org|g' /etc/apt/sources.list.d/debian.sources; \
    if [ -s /run/secrets/ca_bundle ]; then \
      export APT_CONFIG=/tmp/healthconnect-apt.conf; \
      printf 'Acquire::https::CaInfo "/run/secrets/ca_bundle";\n' > "$APT_CONFIG"; \
    fi; \
    apt-get update; \
    apt-get install -y --no-install-recommends ca-certificates libcurl4-openssl-dev libonig-dev libxml2-dev; \
    for extension in curl mbstring pdo_mysql dom simplexml; do \
      if ! php -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$extension"; then \
        docker-php-ext-install -j2 "$extension"; \
      fi; \
    done; \
    rm -rf /var/lib/apt/lists/* /tmp/healthconnect-apt.conf

WORKDIR /var/www/html
COPY website-yte-suckhoe/ /var/www/html/

RUN <<'SHELL'
set -eu
find /var/www/html -type d -exec chmod 0755 {} +
find /var/www/html -type f -exec chmod 0644 {} +
mkdir -p /var/www/html/uploads
cat > /var/www/html/uploads/.htaccess <<'APACHE'
Options -Indexes
<FilesMatch "\.(php[0-9]?|phtml|phar)$">
Require all denied
</FilesMatch>
APACHE
chown -R www-data:www-data /var/www/html/uploads
cat > /etc/apache2/conf-available/healthconnect.conf <<'APACHE'
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
ServerName localhost
APACHE
a2enconf healthconnect
cat > /usr/local/bin/healthconnect-config.php <<'PHP'
<?php
declare(strict_types=1);
$path = '/var/www/html/config.local.php';
if (is_file($path)) {
    exit(0); // Giữ cấu hình riêng khi người vận hành mount file vào container.
}
$config = require '/var/www/html/config.example.php';
$variables = [
    'db_host' => 'HC_DB_HOST', 'db_port' => 'HC_DB_PORT',
    'db_name' => 'HC_DB_NAME', 'db_user' => 'HC_DB_USER', 'db_pass' => 'HC_DB_PASSWORD',
    'app_url' => 'HC_APP_URL', 'setup_key' => 'HC_SETUP_KEY',
    'ai_provider' => 'HC_AI_PROVIDER', 'ai_api_key' => 'HC_AI_API_KEY',
    'ai_model' => 'HC_AI_MODEL', 'ai_url' => 'HC_AI_URL',
    'ollama_url' => 'HC_OLLAMA_URL', 'ollama_model' => 'HC_OLLAMA_MODEL',
    'mail_transport' => 'HC_MAIL_TRANSPORT', 'mail_from' => 'HC_MAIL_FROM',
    'smtp_host' => 'HC_SMTP_HOST', 'smtp_port' => 'HC_SMTP_PORT',
    'smtp_username' => 'HC_SMTP_USERNAME', 'smtp_password' => 'HC_SMTP_PASSWORD',
    'smtp_security' => 'HC_SMTP_SECURITY', 'smtp_auth' => 'HC_SMTP_AUTH',
];
foreach ($variables as $key => $name) {
    $value = getenv($name);
    if ($value !== false) $config[$key] = $value;
}
if ($config['setup_key'] === '') $config['setup_key'] = bin2hex(random_bytes(32));
umask(0027);
if (file_put_contents($path, "<?php\nreturn " . var_export($config, true) . ";\n", LOCK_EX) === false) {
    throw new RuntimeException('Không tạo được cấu hình cục bộ.');
}
chgrp($path, 'www-data');
PHP
cat > /usr/local/bin/healthconnect-entrypoint <<'ENTRYPOINT'
#!/bin/sh
set -eu
php /usr/local/bin/healthconnect-config.php
exec docker-php-entrypoint "$@"
ENTRYPOINT
chmod +x /usr/local/bin/healthconnect-entrypoint
SHELL

EXPOSE 80
ENTRYPOINT ["healthconnect-entrypoint"]
CMD ["apache2-foreground"]
