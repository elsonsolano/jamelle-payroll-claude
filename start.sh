#!/usr/bin/env bash
set -e

echo "==> Running Laravel startup tasks..."
php artisan config:cache
php artisan storage:link --force
php artisan migrate --force

echo "==> Writing PHP-FPM config..."
CURRENT_USER=$(whoami)
CURRENT_GROUP=$(id -gn)

cat > /tmp/php-fpm.conf << FPMEOF
[global]
error_log = /dev/stderr
daemonize = no
pid = /tmp/php-fpm.pid

[www]
user = ${CURRENT_USER}
group = ${CURRENT_GROUP}
listen = 127.0.0.1:9000
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 5
php_admin_value[error_log] = /dev/stderr
php_admin_flag[log_errors] = on
FPMEOF

echo "==> Starting PHP-FPM..."
php-fpm --fpm-config /tmp/php-fpm.conf &

# Wait for FPM socket to be ready
sleep 1

echo "==> Writing nginx config on port ${PORT}..."
mkdir -p /tmp/nginx-tmp /var/log/nginx

cat > /tmp/nginx.conf << NGINXEOF
worker_processes auto;
error_log /dev/stderr warn;
pid /tmp/nginx.pid;

events {
    worker_connections 1024;
}

http {
    types {
        text/html                   html htm shtml;
        text/css                    css;
        text/javascript             js mjs;
        application/json            json;
        image/png                   png;
        image/jpeg                  jpeg jpg;
        image/gif                   gif;
        image/svg+xml               svg svgz;
        image/x-icon                ico;
        image/webp                  webp;
        font/woff                   woff;
        font/woff2                  woff2;
        application/pdf             pdf;
        application/octet-stream    bin;
    }
    default_type application/octet-stream;

    access_log /dev/stdout;
    sendfile on;
    keepalive_timeout 65;
    client_max_body_size 20M;

    client_body_temp_path /tmp/nginx-tmp/client_body;
    fastcgi_temp_path     /tmp/nginx-tmp/fastcgi;
    proxy_temp_path       /tmp/nginx-tmp/proxy;
    uwsgi_temp_path       /tmp/nginx-tmp/uwsgi;
    scgi_temp_path        /tmp/nginx-tmp/scgi;

    server {
        listen ${PORT};
        root /app/public;
        index index.php index.html;

        location / {
            try_files \$uri \$uri/ /index.php?\$query_string;
        }

        location ~ \.php\$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME  \$realpath_root\$fastcgi_script_name;
            fastcgi_param QUERY_STRING     \$query_string;
            fastcgi_param REQUEST_METHOD   \$request_method;
            fastcgi_param CONTENT_TYPE     \$content_type;
            fastcgi_param CONTENT_LENGTH   \$content_length;
            fastcgi_param SCRIPT_NAME      \$fastcgi_script_name;
            fastcgi_param REQUEST_URI      \$request_uri;
            fastcgi_param DOCUMENT_URI     \$document_uri;
            fastcgi_param DOCUMENT_ROOT    \$document_root;
            fastcgi_param SERVER_PROTOCOL  \$server_protocol;
            fastcgi_param HTTPS            \$https if_not_empty;
            fastcgi_param GATEWAY_INTERFACE CGI/1.1;
            fastcgi_param SERVER_SOFTWARE  nginx;
            fastcgi_param REMOTE_ADDR      \$remote_addr;
            fastcgi_param REMOTE_PORT      \$remote_port;
            fastcgi_param SERVER_ADDR      \$server_addr;
            fastcgi_param SERVER_PORT      \$server_port;
            fastcgi_param SERVER_NAME      \$server_name;
            fastcgi_param REDIRECT_STATUS  200;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
}
NGINXEOF

echo "==> Starting nginx..."
exec nginx -c /tmp/nginx.conf -g 'daemon off;'
