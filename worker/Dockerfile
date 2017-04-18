FROM php:7.1-alpine

#RUN apk update

#RUN apt-get update -y \
#  && apt-get install -y \
#    libxml2-dev \
#    php-soap \
#    zlib1g-dev \
#  && apt-get clean -y \
#  && docker-php-ext-install soap \
#  && docker-php-ext-install zip
#RUN docker-php-ext-install soap \

#Install openrc
#RUN apk add openrc --no-cache

#Install tools
#RUN apk add htop nano
##Install Zip
#RUN apk add zlib-dev
#RUN docker-php-ext-install zip
#
#Install bcmath
RUN docker-php-ext-install bcmath
#
##Install composer
#RUN curl -sS https://getcomposer.org/installer | \
#    php -- --install-dir=/usr/bin/ --filename=composer
#Install procps
#RUN apk add procps


COPY ./src /var/attlaz
WORKDIR /var/attlaz
#
ENTRYPOINT ["/var/attlaz/run.php"]
CMD ["worker:start"]
