FROM ubuntu:22.04 as s2low_base

EXPOSE 443 80
WORKDIR /var/www/s2low/

# Install requirements
COPY ./docker-resources/install-requirements.sh /root/
RUN /bin/bash /root/install-requirements.sh

# Create S2LOW needs
COPY ./docker-resources/ /tmp/docker-resources/
RUN /bin/bash /tmp/docker-resources/docker-construction.sh

#Composer
COPY ./composer.* /var/www/s2low/
RUN composer install --ignore-platform-reqs
ENV PATH="${PATH}:/var/www/s2low/vendor/bin/"

COPY --chown=www-data:www-data ./ /var/www/s2low/

#Ajoute droit d'execution sur script monitor ipsec
RUN chmod +x /var/www/s2low/script/divers/ipsec-monitor.sh && \
    ln -s /var/www/parapheur/libersign /var/www/s2low/public.ssl/libersign

ENTRYPOINT ["docker-s2low-entrypoint"]
CMD ["/usr/bin/supervisord","-c","/etc/supervisor/supervisord.conf"]

FROM s2low_base as s2low_dev
RUN /bin/bash /tmp/docker-resources/install-dev-requirements.sh

FROM s2low_base as s2low_prod