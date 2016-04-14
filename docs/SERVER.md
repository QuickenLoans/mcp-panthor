### Web server configuration

- [Back to Documentation](README.md)
- [Application Structure](APPLICATION_STRUCTURE.md)
- [How To Use](USAGE.md)
- [Error Handling](ERRORS.md)
- [Cookies](COOKIES.md)
- Web Server Configuration

Example web server configuration. Note that these configurations listen on port `80` only. To add HTTPS, Add a listener
on `443` and turn on ssl.

Replace the following parameters in each configuration:
- `$SERVER_NAME` - Virtual host name such as `testapplication.local` or `localhost`.
- `$APPLICATION_ROOT` - Full path to project directory such as `/var/www/testapp/`.

#### Apache

```
<VirtualHost *:80>
    ServerName $SERVER_NAME
    DocumentRoot $APPLICATION_ROOT/public

    <Directory $APPLICATION_ROOT/public>
        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [QSA,L]
    </Directory>
</VirtualHost>
```

#### NGINX

```
server {
    listen       80;
    server_name  $SERVER_NAME;
    root         $APPLICATION_ROOT/public;

    location / {
        try_files       $uri        /index.php?$query_string;
    }

    location ~* \.php$ {
        try_files       $uri        /index.php?$query_string;

        fastcgi_connect_timeout     3s;
        fastcgi_read_timeout        10s;

        include         /etc/nginx/fastcgi_params;

        fastcgi_param   SCRIPT_FILENAME     $document_root$fastcgi_script_name;

        fastcgi_pass    127.0.0.1:9000;    # assumes you are running php-fpm locally on port 9000
        fastcgi_index   index.php
    }
}
```
