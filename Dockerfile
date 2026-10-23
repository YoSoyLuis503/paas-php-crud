# ==============================================================================
# Dockerfile - Stack Clásico (PHP 8.2 + MySQL Driver)
# Adaptado para Nube Privada y Restricciones de Seguridad Kubernetes (runAsNonRoot)
# ==============================================================================
FROM php:8.2-cli-alpine

# Instalar dependencias del sistema y extensión pdo_mysql
RUN apk add --no-cache libstdc++ \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_mysql \
    && apk del .build-deps

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de la aplicación
COPY . /var/www/html

# Crear y asignar usuario no privilegiado (UID 101 coincide con la política de seguridad del Helm Chart)
RUN addgroup -g 101 appuser && adduser -u 101 -D -G appuser appuser \
    && chown -R appuser:appuser /var/www/html

USER 101:101

# Puerto dinámico: si se inyecta PORT usa ese, por defecto 8080 (seguro para non-root)
ENV PORT=8080
EXPOSE 8080

# Servidor web integrado de PHP (ligero, ultra rápido y listo para producción en micro-pods)
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /var/www/html"]
