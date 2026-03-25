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

Le script principal est `src/script.php`. Il a été réécrit pour utiliser un flux en **pipeline asynchrone** avec une base SQLite intermédiaire (`SelfDB`), garantissant le transfert de grandes volumétries avec **un seul fichier téléchargé à la fois** pour préserver l'espace disque.

**Syntaxe :**
```bash
php src/script.php --step=<daemon|import|download|upload>
```

**Options (Étape du pipeline) :**
- `--step=daemon` : (Recommandé) Lance le chef d'orchestre en boucle infinie. Il pilote automatiquement l'importation de nouvelles lignes, le téléchargement (1 transaction) puis son upload immédiat vers le nouveau S3 avant de nettoyer le fichier local. Si le log plante, sa relance nettoie instantanément le fichier laissé.
- `--step=import` : Peuple manuellement la base de données locale depuis S2Low avec le statut `HANDLE`.
- `--step=download` : Lance un lot de téléchargements manuel depuis `HANDLE` vers `DOWNLOADED`. 
- `--step=upload` : Lance un lot d'envois manuel depuis `DOWNLOADED` vers le *New S3*, marque en `COMPLETED` et efface les données.

**Exemples :**

1. Lancer la migration complète, sécurisée et asynchrone (mode "one-by-one") :
   ```bash
   php src/script.php --step=daemon
   ```

2. Effectuer des lancements ciblés pour purger (`upload` va agir sur ce qui est actuellement stocké sur le disque) :
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
