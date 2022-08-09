FROM ubuntu:22.04 as s2low_base

ARG UID=33
ARG GID=33
ARG USERNAME=www-data
ARG GROUPNAME=www-data

EXPOSE 443 80
WORKDIR /var/www/s2low/

# Install requirements
COPY ./docker-resources/install-requirements.sh /root/
RUN /bin/bash /root/install-requirements.sh

# Create S2LOW needs
COPY ./docker-resources/ /tmp/docker-resources/
RUN /bin/bash /tmp/docker-resources/docker-construction.sh

# Create Symfony needs
RUN /bin/bash /tmp/docker-resources/create-symfony-structure.sh
#Composer

COPY --chown=${USERNAME}:${GROUPNAME} ./composer.* /var/www/s2low/
RUN composer install --no-dev --no-autoloader && rm -rf /root/.composer/

COPY ./ /var/www/s2low/
RUN composer dump-autoload --no-dev --optimize

ENV PATH="${PATH}:/var/www/s2low/vendor/bin/"

COPY --chown=www-data:www-data ./ /var/www/s2low/
RUN chown ${USERNAME}:${GROUPNAME} /var/www/s2low/

#Ajoute droit d'execution sur script monitor ipsec
RUN chmod +x /var/www/s2low/script/divers/ipsec-monitor.sh && \
    ln -s /var/www/parapheur/libersign /var/www/s2low/public.ssl/libersign

USER "${USERNAME}"

ENTRYPOINT ["docker-s2low-entrypoint"]
CMD ["/usr/bin/supervisord","-c","/etc/supervisor/supervisord.conf"]

FROM s2low_base as s2low_dev

ARG UID=33
ARG GID=33
ARG USERNAME=www-data
ARG GROUPNAME=www-data

USER root
RUN /bin/bash /tmp/docker-resources/install-dev-requirements.sh
USER "${USERNAME}"

FROM s2low_base as s2low_prod