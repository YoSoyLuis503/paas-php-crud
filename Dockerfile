# ==============================================================================
# Dockerfile - Stack Clásico (PHP 8.2 + Apache 2.4 + MySQL Driver)
# Adaptado para Nube Privada y Restricciones de Seguridad Kubernetes (runAsNonRoot)
# ==============================================================================
FROM php:8.2-apache

# Instalar extensión pdo_mysql oficial
RUN docker-php-ext-install pdo pdo_mysql

# Configurar Apache para escuchar en el puerto 8080 (política runAsNonRoot de Kubernetes)
RUN sed -i 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:8080>/g' /etc/apache2/sites-available/000-default.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de la aplicación
COPY . /var/www/html

# Crear usuario 101 y asignar permisos de ejecución para Apache y la aplicación
RUN useradd -u 101 appuser \
    && chown -R 101:101 /var/www/html \
    && chown -R 101:101 /var/log/apache2 \
    && chown -R 101:101 /var/run/apache2

USER 101:101

ENV PORT=8080
EXPOSE 8080

# Servidor HTTP Apache 2.4 en primer plano
CMD ["apache2-foreground"]
