FROM hubdocker.libriciel.fr/node:24.14.0-alpine3.23 AS node_modules
WORKDIR /app/
COPY package*.json ./
RUN npm install
COPY webpack.config.js ./
COPY src_js/ ./src_js/
RUN npx webpack --config webpack.config.js

FROM hubdocker.libriciel.fr/httpd:2.4.67-alpine3.23

RUN apk update && apk upgrade --no-cache && \
    apk add --no-cache \
    bash \
    certbot \
    coreutils \
    curl \
    logrotate \
    openssl \
    supercronic \
    supervisor

COPY ./docker/web/entrypoint.sh /usr/local/bin/
COPY ./docker/validca.sh /usr/local/bin/
COPY ./docker/web/scripts/ /usr/local/bin/

COPY ./docker/web/vhost/ /usr/local/apache2/conf/vhosts/
COPY ./docker/web/security.conf /usr/local/apache2/conf/extra/

COPY ./docker/logrotate.d/s2low.conf /etc/logrotate.d/
COPY ./docker/web/crontab /data/crontab
COPY ./docker/web/supervisord.conf /etc/supervisord.conf

RUN mkdir -p /data/certificates /data/log /data/run  && chown www-data:www-data -R /data/ && \
    echo "PidFile /data/run/httpd.pid" >> /usr/local/apache2/conf/httpd.conf && \
    echo "IncludeOptional conf/vhosts/*.conf" >> /usr/local/apache2/conf/httpd.conf && \
    echo "Include conf/extra/security.conf" >> /usr/local/apache2/conf/httpd.conf && \
    sed -i '/^Listen 80$/a Listen 443' /usr/local/apache2/conf/httpd.conf && \
    sed -i \
    -e 's/#LoadModule http2_module/LoadModule http2_module/' \
    -e 's/#LoadModule headers_module/LoadModule headers_module/' \
    -e 's/#LoadModule proxy_module/LoadModule proxy_module/' \
    -e 's/#LoadModule proxy_http_module/LoadModule proxy_http_module/' \
    -e 's/#LoadModule proxy_fcgi_module/LoadModule proxy_fcgi_module/' \
    -e 's/#LoadModule rewrite_module/LoadModule rewrite_module/' \
    -e 's/#LoadModule socache_shmcb_module/LoadModule socache_shmcb_module/' \
    -e 's/#LoadModule ssl_module/LoadModule ssl_module/' \
    /usr/local/apache2/conf/httpd.conf

COPY ./public /app/public
COPY ./public.ssl /app/public.ssl
COPY --from=node_modules /app/public.ssl/jsmodules/ /app/public.ssl/jsmodules/

USER www-data

EXPOSE 80 443

HEALTHCHECK CMD curl --fail -k https://localhost/ --noproxy '*' || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord"]
