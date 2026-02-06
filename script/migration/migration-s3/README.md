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

Le script principal est `src/script.php`.

**Syntaxe :**
```bash
php src/script.php --type=<type_flux> [--dry-run] [--check]
```

**Options :**
- `--type` : Le type de données à migrer. Valeurs possibles : `actes`, `helios`, `helios_acquit`, `mail`.
- `--dry-run` : (Optionnel) Lance le script en mode simulation (pas d'écriture réelle/suppression).
- `--check` : (Optionnel) Vérifie uniquement la connexion aux services (S3, BDD, etc.).

**Exemples :**

1. Vérifier les connexions :
   ```bash
   php src/script.php --check
   ```

2. Simuler une migration pour le module Actes :
   ```bash
   php src/script.php --type=actes --dry-run
   ```

3. Lancer réellement la migration pour Helios :
   ```bash
   php src/script.php --type=helios
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
