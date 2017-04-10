FROM php:7.1-fpm

RUN apt-get update

#RUN apt-get update -y \
#  && apt-get install -y \
#    libxml2-dev \
#    php-soap \
#    zlib1g-dev \
#  && apt-get clean -y \
#  && docker-php-ext-install soap \
#  && docker-php-ext-install zip
#RUN docker-php-ext-install soap \
#Install Zip
RUN apt-get install zlib1g-dev
RUN docker-php-ext-install zip

#Install bcmath
RUN docker-php-ext-install bcmath

#Install composer
RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/bin/ --filename=composer
