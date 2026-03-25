# Documentation Migration S3

Ce projet contient les scripts nécessaires pour migrer les fichiers vers un stockage S3 pour différents modules (Actes, Helios, Mail).

## Pré-requis

- Docker
- Docker Compose

## Installation

1. **Configuration de l'environnement**
   Copiez le fichier d'exemple et configurez vos variables :
   ```bash
   cp .env.dist .env
   # Éditez le fichier .env avec vos configurations S3, DB, etc.
   ```

2. **Démarrage**
   Construisez l'image et démarrez le conteneur :
   ```bash
   make build
   make start
   ```

3. **Installation des dépendances**
   Installez les librairies PHP via Composer :
   ```bash
   make install
   ```

## Utilisation

Toutes les commandes doivent être exécutées depuis le conteneur ou via `docker exec`.

Pour entrer dans le conteneur :
```bash
make bash
```

### Commande de migration

Le script principal est `src/script.php`. Il a été réécrit pour utiliser un flux en **pipeline asynchrone** avec une base PostgreSQL intermédiaire (`SelfDB`), garantissant le transfert de grandes volumétries avec **un seul fichier téléchargé à la fois** pour préserver l'espace disque.

**Syntaxe :**
```bash
php src/script.php --step=<daemon|import|resolve|download|upload> [--min-date=YYYY-MM-DD] [--type=acte,pes_aller]
```

**Options (Étape du pipeline) :**
- `--step=daemon` : (Recommandé) Lance le chef d'orchestre en boucle infinie (Import -> Resolve -> Download -> Upload).
- `--step=import` : Peuple manuellement la base de données locale (`HANDLE`).
- `--step=resolve` : Cherche le bucket source de la transaction (`BUCKET_FOUND`).
- `--step=download` : Lance un lot de téléchargements manuel (`DOWNLOADED`).
- `--step=upload` : Lance un lot d'envois manuel (`COMPLETED`).

**Options de filtre :**
- `--min-date` (ou `-m`) : Ne considérer que les transactions après une certaine date (Format `YYYY-MM-DD`).
- `--type` (ou `-t`) : Ne traiter que certains types de transactions. Valeurs séparées par des virgules.
  - Alias valides : `acte` (ou `actes`), `pes_aller` (ou `pes`), `pes_acquit` (ou `acquit`), `mail`.

**Exemples :**

1. Lancer la migration complète (mode daemon) :
   ```bash
   php src/script.php --step=daemon
   ```

2. Migrer uniquement les actes et PES aller :
   ```bash
   php src/script.php --step=daemon --type=acte,pes_aller
   ```

3. Importer uniquement les actes depuis janvier 2023 :
   ```bash
   php src/script.php --step=import --type=acte --min-date=2023-01-01
   ```

4. Purger les fichiers locaux (upload ce qui est sur le disque) :
   ```bash
   php src/script.php --step=upload
   ```

## Tests

Pour exécuter les tests unitaires :
```bash
vendor/bin/phpunit
```

## Aide-mémoire Makefile

Voici les raccourcis disponibles via `make` :

| Commande | Description |
|----------|-------------|
| `make build` | Construit l'image Docker |
| `make start` | Démarre le conteneur `php_sqlite_composer` |
| `make stop` | Arrête et supprime le conteneur |
| `make install` | Lance `composer install` dans le conteneur |
| `make bash` | Ouvre un shell interactif dans le conteneur |
| `make test` | Lance les tests unitaires PHPUnit |
