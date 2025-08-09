# PHP 8.3 with Apache (mod_php) — simple + stable
FROM php:8.3-apache

# Enable useful Apache modules
RUN a2enmod rewrite headers expires

# ---- PHP extensions needed by the admin (SQLite + PDO + file info) ----
# pdo/pdo_sqlite are bundled with PHP; docker-php-ext-install compiles them in.
RUN docker-php-ext-install pdo pdo_sqlite

# Optional: raise upload/post limits a bit (tweak to your needs)
RUN { \
      echo "upload_max_filesize=10M"; \
      echo "post_max_size=12M"; \
      echo "memory_limit=256M"; \
   } > /usr/local/etc/php/conf.d/uploads.ini

# ---- Apache vhost with security headers ----
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
    echo '  # Basic CSP (adjust if you add more CDNs)'; \
    echo '  Header always set Content-Security-Policy "default-src '\''self'\''; img-src '\''self'\'' data: https:; style-src '\''self'\'' '\''unsafe-inline'\'' https://fonts.googleapis.com; font-src '\''self'\'' data: https://fonts.gstatic.com; script-src '\''self'\'' '\''unsafe-inline'\'' https://cdn.tailwindcss.com"'; \
    echo '</VirtualHost>'; \
  } > /etc/apache2/sites-available/000-default.conf

# Silence 'Could not reliably determine the server's FQDN'
RUN printf "ServerName admin.movana.me\n" > /etc/apache2/conf-available/servername.conf && \
    a2enconf servername

# Copy app files
COPY . /var/www/html/

# Create data dirs (SQLite DB + uploads) and set permissions
RUN mkdir -p /var/www/html/var/uploads && \
    chown -R www-data:www-data /var/www/html

# Healthcheck: login page should be reachable
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD \
  php -r "exit((@file_get_contents('http://127.0.0.1/login.php')!==false)?0:1);"

EXPOSE 80
# Apache runs in foreground by default
