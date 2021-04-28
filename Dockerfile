FROM aspendigital/octobercms:develop-php7.4-apache

RUN docker-php-ext-install bcmath
RUN apt-get update && apt-get install -y curl dos2unix
RUN apt-get install -y libxrender1 libfontconfig1 libx11-dev libjpeg62 libxtst6
RUN composer global require --prefer-dist hirak/prestissimo --no-interaction

COPY ./src /opt/wcb/src
COPY ./composer.json /opt/wcb/composer.json
COPY ./templates /opt/wcb/templates
COPY ./wcb /opt/wcb/wcb
COPY ./wcb-entrypoint /opt/wcb/wcb-entrypoint

RUN cd /opt/wcb && composer install

RUN mkdir /composer \
    && cd /composer \
    && composer require --prefer-dist laravel/envoy --no-interaction

RUN cd /opt/wcb && dos2unix wcb wcb-entrypoint
RUN cd /opt/wcb && chmod +x wcb && chmod +x wcb-entrypoint

RUN ln -s /opt/wcb/wcb-entrypoint /usr/bin/wcb-entrypoint
RUN ln -s /opt/wcb/wcb /usr/bin/wcb
RUN ln -s /composer/vendor/bin/envoy /usr/bin/envoy

WORKDIR /var/www/html

ENTRYPOINT ["wcb-entrypoint"]
CMD ["apache2-foreground"]
