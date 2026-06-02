[![Minimum PHP Version](http://img.shields.io/badge/php-%208.1-8892BF.svg)](https://php.net/)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![pipeline status](https://gitlab.libriciel.fr/libriciel/pole-plate-formes/s2low/s2low/badges/master/pipeline.svg)](https://gitlab.libriciel.fr/libriciel/pole-plate-formes/s2low/s2low/commits/master)
[![coverage report](https://gitlab.libriciel.fr/libriciel/pole-plate-formes/s2low/s2low/badges/master/coverage.svg)](https://gitlab.libriciel.fr/libriciel/pole-plate-formes/s2low/s2low/commits/master)

# S2low

## Démarrage en mode dev

Prérequis :
- docker
- docker compose

```shell
make env
```
Adapter le fichier `.env` si nécessaire

Si la configuration du fichier `compose.dev.yaml` contient des données à surcharger, il est nécessaire de créer
un fichier `compose.override.yaml`.

Créer les répertoires et donner les bonnes permissions (à adapter si configuration différente d'origine) :

```shell
mkdir -p /data/s2low/web/{certificates,letsencrypt,log}
chown -R 82:82 /data/s2low/web

mkdir -p /data/s2low/app/{certificates/actes_truststore,log,tdt-workspace}
chown -R 1000:1000 /data/s2low/app
```

```shell
make build
make install
make start
```

Accès au site : https://localhost:108443