[![Minimum PHP Version](http://img.shields.io/badge/php-%205.5-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/licence-CeCILL%20v2-blue.svg)](http://www.cecill.info/licences/Licence_CeCILL_V2-fr.html)

#s2low


## Configuration du docker

Le docker est basé sur [php5.6-apache](https://hub.docker.com/_/php/).



Variable d'environnement du docker 

| Variable d'environnement | Signification | Valeur par défaut |
| S2LOW_WEBSITE | URL du site HTTP | |
| S2LOW_WEBSITE_SSL | URL du site HTTPS | |
 

WORKSPACE_VOLUME=/Users/eric/data_docker/s2low/workspace
PHP_SESSION=/Users/eric/data_docker/s2low/session
SERVER_SSL_CERTIFICAT=/Users/eric/data_docker/s2low/certificat/
POSTGRES_USER=tedetis
POSTGRES_PASSWORD=tedetis
POSTGRES_DB=tedetis
POSTGRESQL_DATADIR=/Users/eric/data_docker/s2low/pgdata/

WEB_HTTP_PORT=10080
WEB_HTTPS_PORT=10443
PHPPGADMIN_HTTP_PORT=10001

