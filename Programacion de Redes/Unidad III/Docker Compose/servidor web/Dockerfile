# Imagen base con Apache + PHP
FROM php:8.2-apache

# Instalamos soporte para SQLite3 en PHP
RUN apt-get update && \
    apt-get install -y libsqlite3-dev && \
    docker-php-ext-install sqlite3 && \
    rm -rf /var/lib/apt/lists/*

# Activamos el módulo rewrite por si luego lo quieres usar
RUN a2enmod rewrite

# Copiamos el contenido inicial del sitio
# (Luego se sobreescribirá por el volumen ./www:/var/www/html)
COPY ./www /var/www/html

# Directorio donde guardaremos la base de datos
RUN mkdir -p /var/www/data

# Damos permisos básicos (para demo; en producción habría que afinar)
RUN chown -R www-data:www-data /var/www

# Apache escucha en el puerto 80 (por defecto)
EXPOSE 80
