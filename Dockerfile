# Usar una imagen base con PHP y Apache
FROM php:8.1-apache

# Habilitar el módulo de Apache rewrite (necesario para algunas configuraciones)
RUN a2enmod rewrite

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

# Copiar los archivos de tu aplicación al contenedor
COPY . /var/www/html/

# Establecer permisos adecuados
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80 (puerto por defecto de Apache)
EXPOSE 80

# Comando para iniciar Apache en primer plano
CMD ["apache2-foreground"]
