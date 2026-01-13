# On part d'une image PHP légère (CLI est suffisant pour du dev/scripting)
FROM php:8.3-cli-bookworm

# Mise à jour des dépôts et installation des dépendances nécessaires
# - libsqlite3-dev et sqlite3 : pour la base de données
# - unzip : requis par Composer pour extraire les packages
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo_sqlite pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installation de Composer de manière propre (on récupère l'exécutable depuis l'image officielle)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définition du répertoire de travail dans le conteneur
WORKDIR /var/www/html

# Par défaut, on lance un terminal interactif PHP
CMD ["/bin/bash"]
