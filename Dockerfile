# Dockerfile for testing purposes

FROM php:5.6
MAINTAINER shahar@shoppimon.com

RUN DEBIAN_FRONTEND=noninteractive apt-get -y update

# Install some required PHP extensions and libraries
RUN DEBIAN_FRONTEND=noninteractive apt-get -y install libzmq3-dev \
    && pecl install channel://pecl.php.net/zmq-1.1.3 \
    && docker-php-ext-enable zmq \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install composer.phar
RUN DEBIAN_FRONTEND=noninteractive apt-get -y install unzip
ADD docker/install-composer.sh /tmp/install-composer.sh
RUN bash /tmp/install-composer.sh && rm /tmp/install-composer.sh

ADD docker/php.ini /usr/local/etc/php/php.ini
