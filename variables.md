# Variables d'environnement du docker

## Pour l'application S²LOW
| Nom de la variable                      | valeur par defaut                 | Description                                                                                                                                                          |  Nom de la variable interne (si différente) |
|-----------------------------------------|-----------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------| ------------------------------ |
| `S2LOW_WEBSITE`                         | `s2low.docker.libriciel.fr`       | Définie l'URL de l'application                                                                                                                                       |`WEBSITE` et `WEBSITE_SSL`|
| `POSTGRES_HOST`                         | `db`                              | Renseigne l'adresse du serveur PostgreSQL                                                                                                                            |`DB_HOST`|
| `POSTGRES_DB`                           | `s2lowdb`                         | Nom de la base de données                                                                                                                                            |`DB_DATABASE`|
| `POSTGRES_USER`                         | `s2lowuser`                       | Nom de l'utilisateur d'accès à la base de données                                                                                                                    |`DB_USER`|
| `POSTGRES_PASSWORD`                     | `s2lowpassword`                   | Mot de passe de l'utilisateur `POSTGRES_USER`                                                                                                                        |`DB_PASSWORD`|
| `HELIOS_FTP_P_APPLI`                    | `THELPES2`                        | identifiant CFT des flux HELIOS                                                                                                                                      ||
| `HELIOS_FTP_SERVER`                     | `ftp`                             | Défini l'adresse du serveur FTP utilisé pour l'envoi non Passtrans                                                                                                                                     ||
| `HELIOS_FTP_PORT`                       | `21`                              | Spécifie le port du serveur FTP                                                                                                                                      ||
| `HELIOS_FTP_PASSIVE_MODE`               | `false`                           | Permet d'indiquer si l'on doit être en mode passif sur le serveur FTP                                                                                                ||
| `HELIOS_FTP_LOGIN`                      | `helios`                          | Nom de l'utilisateur sur le serveur FTP                                                                                                                              ||
| `HELIOS_FTP_PASSWORD`                   | `helios`                          | Mot de passe associé à l'utilisteur FTP                                                                                                                              |
| `HELIOS_FTP_CONNECTION_MODE`            | `SIMULATEUR`                      | Utilise un des modes de connexion parmi `SIMULATEUR`, `GATEWAY`, `PASSTRANS_SFTP`, `PASSTRANS_FTPS`                                                                  ||
| `HELIOS_SENDING_DESTINATION`            | `/entree/`                        | On spécifie le répertoire où l'application dépose les PES_ALLER/PES_RETOUR_ACQUIT                                                                                    ||
| `HELIOS_FTP_RESPONSE_SERVER_PATH`       | `/sortie/`                        | On spécifie le répertoire où l'application récupère les PES_ACQUIT, PES_RETOUR et flux OCRE                                                                          ||
| `HELIOS_PASSTRANS_SERVER`               | `ftp_passtrans`                   | Défini l'adresse du serveur Passtrans                                                                                                                                     ||
| `HELIOS_PASSTRANS_PORT`                 | `21`                              | Spécifie le port du serveur Passtrans                                                                                                                                      ||
| `HELIOS_PASSTRANS_PASSIVE_MODE`         | `false`                           | Permet d'indiquer si l'on doit être en mode passif sur le serveur Passtrans                                                                                                ||
| `HELIOS_PASSTRANS_LOGIN`                | `helios-passtrans`                | Nom de l'utilisateur sur le serveur Passtrans                                                                                                                              ||
| `HELIOS_PASSTRANS_PASSWORD`             | `helios-passtrans`                | Mot de passe associé à l'utilisteur Passtrans                                                                                                                              |
| `HELIOS_PASSTRANS_CONNECTION_MODE`      | `SIMULATEUR`                      | Utilise un des modes de connexion parmi `SIMULATEUR`, `GATEWAY`, `PASSTRANS_SFTP`, `PASSTRANS_FTPS`                                                                  ||
| `HELIOS_PASSTRANS_SENDING_DESTINATION`  | `/entree/`                        | On spécifie le répertoire où l'application dépose les PES_ALLER/PES_RETOUR_ACQUIT                                                                                    ||
| `HELIOS_PASSTRANS_RESPONSE_SERVER_PATH` | `/sortie/`                        | On spécifie le répertoire où l'application récupère les PES_ACQUIT, PES_RETOUR et flux OCRE                                                                          ||
| `POSTGRES_HOST_TEST`                    | `dbtest`                          | Renseigne l'adresse du serveur PostgreSQL pour la base de données de tests                                                                                           |`DB_HOST_TEST`|
| `POSTGRES_DB_TEST`                      | `s2lowdbtest`                     | Nom de la base de données de tests                                                                                                                                   |`DB_DATABASE_TEST`|
| `POSTGRES_USER_TEST`                    | `s2lowusertest`                   | Nom de l'utilisateur d'accès à la base de données de tests                                                                                                           |`DB_USER_TEST`|
| `POSTGRES_PASSWORD_TEST`                | `s2lowpasswordtest`               | Nom de l'utilisateur d'accès à la base de données de tests                                                                                                           |`DB_PASSWORD_TEST`|
| `OPENSTACK_AUTHENTICATION_URL_V3`       | `https://auth.cloud.ovh.net/v3`   | URL de l'API Openstack pour le stockage objet des PESv2                                                                                                              |
| `OPENSTACK_USERNAME`                    |                                   | Indique le nom de l'utilisateur pour les API Openstack `OPENSTACK_AUTHENTICATION_URL_V3`                                                                             |
| `OPENSTACK_PASSWORD`                    |                                   | Mot de passe de l'utilisateur `OPENSTACK_USERNAME`                                                                                                                   |
| `OPENSTACK_TENANT`                      | `0750189044_S2LOWDEV`             | Tenant dans lequel seront stockés les objets                                                                                                                         |
| `OPENSTACK_REGION`                      | `fr1`                             | Région du datacenter de l'API Openstack                                                                                                                              |
| `OPENSTACK_SWIFT_CONTAINER_PREFIX`      | `s2low_dev_`                      | Préfix pour les containers des stockages objets                                                                                                                      |
| `PADES_VALID_URL`                       | `http://pades-valid:8080`         | URL d'accès au service de validation des PDF PADES                                                                                                                   ||
| `PDF_STAMP_URL`                         | `http://pdf-stamp:8080/pdf-stamp/` | URL d'accès au service d'application du tampon sur un PDF                                                                                                            |
| `ACTES_TDT_MAIL_ADDRESS`                | `s2low@s2low.docker.libriciel.fr` | Permet d'indiquer l'adresse mail retour pour les ACK du MI. Cette adresse est définie par la variable `MAIL_ADRESS` dans le container `mail`/`ACTES_IMAP_HOST`       |
| `ACTES_IMAP_HOST`                       | `mail`                            | Permet d'indiquer l'adresse du serveur de mails pour les retour des ACK du MI.                                                                                       |
| `ACTES_IMAP_LOGIN`                      | `s2low@s2low.docker.libriciel.fr` | Identifiant de l'utilisateur correspondant à l'adresse mail retour des ACK du MI. Par défaut, cela doit correspondre à la valeur `MAIL_ADDRESS` du container `mail`. |
| `ACTES_IMAP_PASSWORD`                   | `password`                        | Mot de passe de l'utilisateur `ACTES_IMAP_LOGIN`                                                                                                                     |


### Pour la configuration système
|Container| Nom de la variable | valeur par defaut | Description |
| ------------------------------ | ------------------------------ | ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| Application S²LOW|`MAILER_DSN`|`smtp://mailsec:25?verify_peer=0`|Adresse du serveur SMTP pour pouvoir envoyer des mails|
| Application S²LOW|`PUBLISH_SIMULATEUR`|`false`|Permet d'ajouter une redirection web vers le simulateur depuis l'adresse `S2LOW_WEBSITE`/simulateur|
| Application S²LOW|`LETSENCRYPT_DOMAIN`||Si la variable est présente, elle permet de créer un certificat `letsencrypt` et de lancer le renouvellement. La valeur doit être égale à `S2LOW_WEBSITE`|
| Application S²LOW|`LETSENCRYPT_EMAIL`||Si la variable est présente, elle permet de créer un certificat `letsencrypt` et de lancer le renouvellement. La valeur doit être égale à `S2LOW_WEBSITE`|
| Application S²LOW|`WEB_HTTP_PORT`|`80`|Permet de spécifier le port d'écoute sur la partie http|
| Application S²LOW|`WEB_HTTPS_PORT`|`443`|Permet de spécifier le port d'écoute sur la partie http|
| Application S²LOW|`S2LOW_URL_PATH`|null|Permet de spécifier un chemin d'accès à l'application|


## Pour les volumes
| Container |Nom de la variable | Destination | Description                                                                              | Exemple |
| ------ | ------ | ------ |------------------------------------------------------------------------------------------| ------ |
| Application S²LOW |`WORKSPACE_VOLUME`|`/data/tdt-workspace/`| Permet de définir le volume  pour les données de l'application S²LOW                     | mapping direct `/data/workspace` ou `workspace` définit via `docker volume create workspace`|
|Application S²LOW|`S2LOW_SSL_CERTIFICAT`|`/etc/s2low/ssl/`| Dans ce volume seront stockés les certificats utilisés par S²LOW                         |mapping direct `/data/certificats2low` ou `certificats2low` définit via `docker volume create certificats2low`|
|Application S²LOW|`LETSENCRYPT_DATADIR`|`/etc/letsencrypt/`| Dans ce volume seront stockés les certificats letsencrypt utilisés par le vhost de S²LOW |mapping direct `/data/letsencrypt/` ou `letsencrypt` définit via `docker volume create letsencrypt`|
|Postgres|`POSTGRESQL_DATADIR`|`/var/lib/postgresql/data`| Persistence de la base de données                                                        |mapping direct `/data/pgdata/` ou `pgdata` définit via `docker volume create pgdata` |
|Postgres test|`POSTGRESQL_DATADIR_TEST`|`/var/lib/postgresql/data`| Persistence de la base de données de tests                                               |mapping direct `/data/pgdatatests/` ou `pgdatatests` définit via `docker volume create pgdatatests` |
|Simulateur |`SIMULATEUR_WORKSPACE_DIRECTORY`|`/var/www/simulateur-helios/workspace`| Ce volume est obligatoire car il doit être partagé avec le serveur FTP                   |mapping direct `/data/simualateur/` ou `simulateur` définit via `docker volume create simulateur` |
|ftp |`SIMULATEUR_WORKSPACE_DIRECTORY`|`/data/$HELIOS_FTP_LOGIN`| Ce volume est obligatoire car il doit être partagé avec le simulateur                    |mapping direct `/data/simualateur/` ou `simulateur` définit via `docker volume create simulateur` |
|redis|`REDIS_VOLUME`|`/data/`| Permet de définir le volume  pour les données persistante de Redis                       |mapping direct `/data/` |


## Pour le serveur Postgres principal, `db`
| Nom de la variable | A mettre par défautl |Description |
| -------- | -------- | -------- |
| `POSTGRES_USER` | `s2lowuser` | Cette valeur sera à renseignée dans l'application S²LOW dans la macro `POSTGRES_USER`|
| `POSTGRES_PASSWORD` | `s2lowpassword` | Cette valeur sera à renseignée dans l'application S²LOW dans la macro `POSTGRES_PASSWORD`|
| `POSTGRES_DB` | `s2lowdb` | Cette valeur sera à renseignée dans l'application S²LOW dans la macro `POSTGRES_DB`|


## Pour le serveur Postgres de test, `dbtest`
| Nom de la variable | A mettre par défautl |Description |
| -------- | -------- | -------- |
| `POSTGRES_USER` | `s2lowusertest` | Cette valeur sera à renseignée dans l'application S²LOW dans la macro `POSTGRES_USER_TEST`|
| `POSTGRES_PASSWORD` | `s2lowpasswordtest` | Cette valeur sera à renseignée dans l'application S²LOW dans la macro `POSTGRES_PASSWORD_TEST`|
| `POSTGRES_DB` | `s2low_test` | Cette valeur sera à renseignée dans l'application S²LOW dans la macro `POSTGRES_DB_TEST`|


## Pour le `simulateur`
| Nom de la variable | A mettre par défaut | Description |
| ------------------------------ | ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| `SITE_INDEX`|`localhost`|Définit l'URL d'accès au simulateur|
| `MAIL_HOST`|`mail`|Définit l'adresse du serveur de mail pour envoyer les ack des actes. Cette valeur doit être la même que celle définit pour l'application S²LOW dans `ACTES_IMAP_HOST` et dans `MAIL_ADRESS` du docker `mail`|
| `MAIL_PORT`|`25`|Définit le port SMTP du serveur de mail pour envoyer les ack des actes.|
| `MAIL_FROM`|`dgfip@mail.docker.libriciel.fr`|Définit l'adresse émétrice pour les mails d'acquittement. Par exemple : `dgcl@mail.docker.libriciel.fr`|
| `SIMULATEUR_SITE_PORT`||Cette variable sert pour le mapping de port vers 80|


## Pour le serveur `ftp`
| Nom de la variable | A mettre par défaut | Description |
| ------------------------------ | ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| `FTP_USER`|`helios`|Nom de l'utilisateur FTP qui est aussi renseigné dans S²LOW,`HELIOS_FTP_LOGIN`|
| `FTP_PASS`|`helios`|Mot de passe de l'utilisateur FTP qui est aussi renseigné dans S²LOW,`HELIOS_FTP_PASSWORD`|
| `FTP_PORT`|`21`|Mapping du port FTP|
| `PASV_MIN_PORT`|`65000`|Début de la plage de ports du serveur FTP|
| `PASV_MAX_PORT`|`65004`|Fin de la plage de ports du serveur FTP|


## Pour le serveur `mail` utilisé pour ACTES (`simulateur` et l'application S²LOW)
| Nom de la variable | A mettre par défaut | Description |
| ------------------------------ | ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| `MAILNAME`|`s2low.docker.libriciel.f`|Nom du domaine géré par postfix|
| `MAIL_ADDRESS`|`s2low@s2low.docker.libriciel.fr`|Adresse le BAL. C'est à cette adresse que seront envoyés les ACK de la DGCL. Cette valeur sera la même que pour `ACTES_TDT_MAIL_ADDRESS` et `ACTES_IMAP_LOGIN`|
| `MAIL_PASS`|`password`|Mot de passe du la BAL `MAIL_ADDRESS` (et donc `ACTES_TDT_MAIL_ADDRESS` et `ACTES_IMAP_LOGIN`). Cette valeur doit être la même que pour `ACTES_IMAP_PASSWORD`|
| `POSTFIX_HOSTNAME`|`s2low-mailsec.docker.libriciel.fr`|Domaine principal du serveur postfix|


## Pour le serveur `mailsec` utilisé pour le mail sécurisé et l'envoie de mails vers l'extérieur
| Nom de la variable | A mettre par défaut | Description |
| ------------------------------ | ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| `DOMAINS`|`s2low.docker.libriciel.f`|liste (séparée par des espaces) des domaines pour lesquels vous souhaitez créer des boites mails.|
| `MAILS`|`s2low-mailsec@s2low.docker.libriciel.fr`|liste (séparée par des espaces) des adresses mails que vous souhaitez créer.|
| `NPASS`|`true`|true si définit les comptes utilisateurs auront pour mot de passe le début de l'adresse mail (exemple : utilisateur s2low-mailsec@s2low.docker.libriciel.fr => mot de passe : s2low-mailsec)|
| `POSTFIX_HOSTNAME`|`s2low-mailsec.docker.libriciel.fr`|Domaine principal du serveur postfix|


## Pour le serveur phppgadmin
| Nom de la variable | A mettre par défaut | Description |
| ------------------------------ | ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| `DB_HOST`|`db`|Adresse d'accès au serveur Postgres principal|
| `DB_PORT`|`5432`|Définit le port d'accès au serveur Postgres|
| `PHPPGADMIN_HTTP_PORT`||Cette variable sert pour le mapping de port vers 80|
