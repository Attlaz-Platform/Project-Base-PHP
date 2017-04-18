FROM richarvey/nginx-php-fpm
#Install bcmath
RUN docker-php-ext-install bcmath

ENV fpm_conf /usr/local/etc/php-fpm.d/www.conf

#Increase max children so we can handle a lot of async tasks
RUN sed -i \
            -e "s/pm.max_children = 4/pm.max_children = 100/g" \
            -e "s/pm.start_servers = 3/pm.start_servers = 10/g" \
            -e "s/pm.min_spare_servers = 2/pm.min_spare_servers = 2/g" \
            -e "s/pm.max_spare_servers = 4/pm.max_spare_servers = 4/g" \
            -e "s/;pm.max_requests = 200/pm.max_requests = 500/g" \
            ${fpm_conf}
