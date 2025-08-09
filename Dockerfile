# PHP 8.3 with Apache (mod_php) — simple + stable
FROM php:8.3-apache

# Enable useful Apache modules
RUN a2enmod rewrite headers expires

# Security headers & tight defaults via Apache vhost
# (kept inline for simplicity; you can move to a separate conf file if you prefer)
RUN set -eux; \
  { \
    echo '<VirtualHost *:80>'; \
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

# Copy app files
# (Assumes Dockerfile sits next to config.php, login.php, index.php, logout.php)
COPY . /var/www/html/

# Tighten permissions a bit
RUN chown -R www-data:www-data /var/www/html

# Healthcheck: login page should be reachable
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD \
  php -r "exit((@file_get_contents('http://127.0.0.1/login.php')!==false)?0:1);"

EXPOSE 80

# Apache runs in foreground by default
