FROM hubdocker.libriciel.fr/node:26.3.1-alpine3.24@sha256:a2dc166a387cc6ca1e62d0c8e265e49ca985d6e60abc9fe6e6c3d6ce8e63f606 AS node_modules
WORKDIR /app/
COPY package*.json ./
RUN npm install
COPY webpack.config.js ./
COPY src_js/ ./src_js/
RUN npx webpack --config webpack.config.js

FROM hubdocker.libriciel.fr/php:8.5.7-fpm-alpine3.24@sha256:fd9669cdc9caa2bc4857a2c9f6b5e86c47a7f85043a6717c29901ca4a8b1cd4e AS base

ARG UID=10001
ARG GID=10001

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="10000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="192" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10"

WORKDIR /app

EXPOSE 9000

RUN apk add --no-cache \
    bash \
    bash-completion \
    c-client \
    ca-certificates \
    clamav-clamdscan \
    findutils \
    git \
    icu \
    icu-data-full \
    imagemagick \
    krb5 \
    libpng \
    libpq \
    libssh2 \
    libzip \
    logrotate \
    oniguruma \
    poppler-utils \
    supercronic \
    supervisor \
    wget \
    xmlsec \
    xmlstarlet

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    curl-dev \
    icu-dev \
    imagemagick-dev \
    imap-dev \
    krb5-dev \
    libpng-dev \
    libssh2-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
  && pecl install imagick imap redis ssh2 \
  && docker-php-ext-install -j$(nproc) \
    ftp \
    gd \
    intl \
    mbstring \
    pcntl \
    pdo_pgsql \
    pgsql \
    zip \
  && docker-php-ext-enable \
    ftp \
    gd \
    imagick \
    imap \
    intl \
    mbstring \
    pcntl \
    pdo_pgsql \
    pgsql \
    redis \
    ssh2 \
    zip \
  && apk del .build-deps

RUN addgroup -S app -g "$GID" && adduser -S app -G app -u "$UID"

COPY ./docker/app/scripts/ /usr/local/bin/
COPY ./docker/validca.sh /usr/local/bin/
COPY ./docker/app/entrypoint.sh /usr/local/bin/
COPY ./docker/logrotate.d/s2low.conf /etc/logrotate.d/
COPY ./docker/app/clamav/ /etc/clamav/
COPY ./docker/app/supervisord.conf /etc/supervisord.conf
COPY ./docker/app/supervisord/ /etc/supervisor/conf.d/
COPY ./docker/app/php/ /usr/local/etc/php/conf.d/

RUN bash -c "mkdir -p /data/{certificates,config,log,run,tdt-workspace}" \
 && chown "$UID":"$GID" -R /data/

COPY --from=node_modules /app/node_modules/ /app/node_modules/
COPY --from=hubdocker.libriciel.fr/composer:2.10.2@sha256:098c43210b4efc3f46d8734dbc7204ee8c159c48ab03511fc5bfa513a08be042 /usr/bin/composer /usr/local/bin/composer
COPY ./composer.* /app/

RUN composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --no-autoloader

COPY --chown=app:app ./ /app/
COPY --from=node_modules --chown=app:app /app/public.ssl/jsmodules/ /app/public.ssl/jsmodules/
COPY --from=node_modules --chown=app:app /app/public.ssl/custom/styles/bootstrap5.min.css /app/public.ssl/custom/styles/bootstrap5.min.css
COPY --from=node_modules --chown=app:app /app/public.ssl/custom/js/bootstrap.bundle.min.js /app/public.ssl/custom/js/bootstrap.bundle.min.js

RUN sh /app/docker/app/add-legacy-provider-to-openssl-v3.sh \
    && composer run-script --no-dev post-install-cmd && composer dump-autoload --no-dev --optimize \
    && rm /usr/local/bin/composer

USER "app"

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

FROM base AS dev

USER "root"

COPY --from=hubdocker.libriciel.fr/composer:2.10.2@sha256:098c43210b4efc3f46d8734dbc7204ee8c159c48ab03511fc5bfa513a08be042 /usr/bin/composer /usr/local/bin/composer

RUN --mount=type=bind,from=ghcr.io/php/pie:1.4.8-bin@sha256:65d91cecb73cb1fd03ae2a892d8ec76b25d34468532c0c2032afa9c412cf8b43,source=/pie,target=/usr/local/bin/pie \
    apk add --no-cache --virtual .dev-build-deps \
    $PHPIZE_DEPS \
    linux-headers \
 && pie install xdebug/xdebug \
 && docker-php-ext-enable xdebug \
 && apk del .dev-build-deps

RUN composer install
RUN chown app:app /app

USER "app"

FROM base AS prod
