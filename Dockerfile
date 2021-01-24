FROM php:7-apache

RUN docker-php-ext-install mysqli
RUN a2enmod rewrite
