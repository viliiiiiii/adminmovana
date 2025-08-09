# PHP 8.3 with Apache (mod_php)
FROM php:8.3-apache

# Enable useful Apache modules
RUN a2enmod rewrite headers expires

# Postgres PDO (add this near the top, before copying your app)
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends libpq-dev; \
    docker-php-ext-install -j"$(nproc)" pdo_pgsql; \
    rm -rf /var/lib/apt/lists/*


# Optional: raise upload/post limits
RUN { \
      echo "upload_max_filesize=10M"; \
      echo "post_max_size=12M"; \
      echo "memory_limit=256M"; \
   } > /usr/local/etc/php/conf.d/uploads.ini

# ---- Apache vhost with headers ----
RUN set -eux; \
  { \
    echo '<VirtualHost *:80>'; \
    echo '  ServerName admin.movana.me'; \
    echo '  ServerAdmin admin@movana.me'; \
    echo '  DocumentRoot /var/www/html'; \
    echo '  <Directory /var/www/html>'; \
    echo '    Options -Indexes +FollowSymLinks'; \
    echo '    AllowOverride All'; \
    echo '    Require all granted'; \
    echo '  </Directory>'; \
    echo '  Header always set X-Content-Type-Options "nosniff"'; \
    echo '  Header always set X-Frame-Options "SAMEORIGIN"'; \
    echo '  Header always set Referrer-Policy "no-referrer-when-downgrade"'; \
    echo '  Header always set Content-Security-Policy "default-src '\''self'\''; img-src '\''self'\'' data: https:; style-src '\''self'\'' '\''unsafe-inline'\'' https://fonts.googleapis.com; font-src '\''self'\'' data: https://fonts.gstatic.com; script-src '\''self'\'' '\''unsafe-inline'\'' https://cdn.tailwindcss.com"'; \
    echo '</VirtualHost>'; \
  } > /etc/apache2/sites-available/000-default.conf

# Silence FQDN warning
RUN printf "ServerName admin.movana.me\n" > /etc/apache2/conf-available/servername.conf && \
    a2enconf servername

# Copy app
COPY . /var/www/html/

# Data dirs (SQLite DB + uploads) and permissions
RUN mkdir -p /var/www/html/var/uploads && \
    chown -R www-data:www-data /var/www/html

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD \
  php -r "exit((@file_get_contents('http://127.0.0.1/login.php')!==false)?0:1);"

EXPOSE 80
