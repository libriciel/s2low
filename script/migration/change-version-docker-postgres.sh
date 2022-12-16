#!/bin/bash

# ⛔️ NE JAMAIS UTILIER EN PRODUCTION ⛔️

# Script de passage de la base de données de postgres lorsqu'elle est en docker.
# Le programme va réaliser un dump de votre base de données, supprimer le contenu du volume/bind postgres,
# réinistaller postgres vierge dans la nouvelle version et réimporter le dump


#### Déclaration fonctions

function help(){
    echo 'Voici les instructions à suivre
- Votre S²LOW (mode prod ou dev) doit être démarré
- Changez la valeur des images postgres (dans docker-compose.yml et docker-compose.dev.yml)
- Vous pouvez lancer le script ./scrip/migration/change-version-docker-postgres.sh

Options :
- h affiche l aide
- d log mode debug
- n ne pas lancer les dump dans le cas où vous les avez déjà

Pre-requis :
- installer jq sur votre poste sudo apt install jq
- docker-compose doit être en version 2.x

Point de vigilence :
- faire une sauvegare de votre PC
- les valeurs par defaut des variables POSTGRES ont changées. Si vous utilisez les anciennes valeurs, il faut les indiquer dans votre .env'
}

function loginfo(){
    echo "🔎 INFO : $1"
}

function logdebug(){
    if [ "${DEBUG}" = true ]
    then
        echo "🛃 DEBUG : $1"
    fi
}

function scriptdeletevol(){
    cat << EOF > /tmp/scriptdelete.sh
#!/bin/bash

rm -r /tmp/vol/p*
rm -r /tmp/vol/P*
rm -r /tmp/vol/base
rm -r /tmp/vol/global
rm /tmp/vol/dump*.sql
EOF

chmod +x /tmp/scriptdelete.sh
}


#### Main
DEBUG=false
DUMP=true
PATHDUMP='dump.sql'
PATHDUMPTEST='dumptest.sql'
DOCKER_COMPOSE='docker-compose -f docker-compose.yml'


while getopts ":hdn" option; do
   case $option in
        h) # display Help
            help
            exit;;
        d) # enable loglevel DEBUG
            DEBUG=true
            ;;
        n) DUMP=false
            ;;
        *)
   esac
done

loginfo 'Controle si db_test existe'
DB_TEST_ENABLE=false
CONTAINER_DBTEST_NAME=$(docker-compose -f docker-compose.yml ps | grep 'db_test' | awk '{print $1}')
if [ "${CONTAINER_DBTEST_NAME}" ]
then
    logdebug "Le service db_test se nomme : ${CONTAINER_DBTEST_NAME}"
    DB_TEST_ENABLE=true
fi


# service DB
# Récupération des variables
loginfo 'Récupération des informations concernant le service db'
CONTAINER_DB_NAME=$(docker-compose -f docker-compose.yml ps | grep 'db-' | awk '{print $1}')
logdebug "Le service db se nomme : ${CONTAINER_DB_NAME}"

POSTGRES_DB=$(docker-compose config db | grep POSTGRES_DB | sed -e 's/POSTGRES_DB: //' | tr -d ' ')
POSTGRES_USER=$(docker-compose config db | grep POSTGRES_USER | sed -e 's/POSTGRES_USER: //' | tr -d ' ')
DIRPOSTGRES=$(docker inspect ${CONTAINER_DB_NAME} | jq '.[].Mounts[] | select(.Destination == "/var/lib/postgresql/data") |.Source' | sed -e 's/\"//g')

logdebug "Postgres db : ${POSTGRES_DB}"
logdebug "Postgres user : ${POSTGRES_USER}"
logdebug "directory postgres on host : ${DIRPOSTGRES}"

loginfo 'Toutes les informations sont récupérées. Passage au dump...'

if [ ${DB_TEST_ENABLE} ]
then
    DOCKER_COMPOSE="${DOCKER_COMPOSE} -f docker-compose.test.yml -f docker-compose.dev.yml"
fi

# Dump de la base de données
if [ "${DUMP}" == true ]
then
    logdebug '⏯ Demarrage du sevice db'
    ${DOCKER_COMPOSE} start db
    logdebug '📤 Dump de la bdd'
    docker exec -it ${CONTAINER_DB_NAME} pg_dump -O -U postgres -d ${POSTGRES_DB} > ${PATHDUMP}
fi

logdebug 'Stop du service db'
${DOCKER_COMPOSE} stop db

loginfo "Vérification si le dossiser ${DIRPOSTGES} d'ou la demande de sudo"
if [ "$(sudo ls -A ${DIRPOSTGRES})" ]
then
     
    logdebug 'Création script de suppression du dossier postgres'
    scriptdeletevol

    logdebug 'Suppression du contenu du dossier postgres'

    sudo docker run --rm -it -v "${DIRPOSTGRES}:/tmp/vol" -v "/tmp/scriptdelete.sh:/tmp/scriptdelete.sh" ubuntu:22.04 /tmp/scriptdelete.sh
fi

loginfo 'ℹ Nous allons lancer la nouvelle base de données. Vous pouvez changer le fichier docker-compose.yml'
loginfo '⏳ en attente que vous confirmiez le changement'
loginfo 'Appuyer sur la touche Entree de votre clavier pour cela'
read -r

loginfo 'Lancement service db'
${DOCKER_COMPOSE} up -d db
loginfo '⏳ on attend que postgres se lance'
sleep 30
loginfo 'Copie du fichier dump.sql dans le container db'
docker cp ${PATHDUMP} ${CONTAINER_DB_NAME}:/var/lib/postgresql/data/dump.sql
loginfo 'Import des données...'
docker exec -it ${CONTAINER_DB_NAME} psql -U ${POSTGRES_USER} -d ${POSTGRES_DB} -f /var/lib/postgresql/data/dump.sql

# Contrôle si la base de données de test existe
loginfo 'Récupération des informations concernant le service db-test'
if [ ${DB_TEST_ENABLE} ]
then
    POSTGRES_DBTEST=$(docker-compose -f docker-compose.dev.yml -f docker-compose.yml -f docker-compose.test.yml config db_test | grep POSTGRES_DB | sed -e 's/POSTGRES_DB: //' | tr -d ' ')
    POSTGRES_USERTEST=$(docker-compose -f docker-compose.dev.yml -f docker-compose.yml -f docker-compose.test.yml config db_test | grep POSTGRES_USER | sed -e 's/POSTGRES_USER: //' | tr -d ' ')
    DIRPOSTGRESTEST=$(docker inspect ${CONTAINER_DBTEST_NAME} | jq '.[].Mounts[] | select(.Destination == "/var/lib/postgresql/data") | .Source' | sed -e 's/\"//g')

    logdebug "Postgres db : ${POSTGRES_DBTEST}"
    logdebug "Postgres user : ${POSTGRES_USERTEST}"
    logdebug "directory postgres on host : ${DIRPOSTGRESTEST}"

    loginfo 'Toutes les informations sont récupérées. Passage au dump de tests...'
    # Dump de la base de données de test
    if [ "${DUMP}" == true ]
    then
        logdebug '⏯ Demarrage du service db_test'
        ${DOCKER_COMPOSE} start db_test
        logdebug '📤 Dump de la bdd'
        docker exec -it ${CONTAINER_DBTEST_NAME} pg_dump -O -U postgres -d ${POSTGRES_DBTEST} > ${PATHDUMPTEST}
    fi

    loginfo "Vérification si le dossiser ${DIRPOSTGRESTEST} d'ou la demande de sudo"
    if [ "$(sudo ls -A ${DIRPOSTGRESTEST})" ]
    then
        ${DOCKER_COMPOSE} stop db_test
        logdebug 'Suppression du contenu du dossier postgres'
        sudo docker run --rm -it -v "${DIRPOSTGRESTEST}:/tmp/vol" -v "/tmp/scriptdelete.sh:/tmp/scriptdelete.sh" ubuntu:22.04 /tmp/scriptdelete.sh
    fi

    loginfo 'ℹ Nous allons lancer la nouvelle base de données. Vous pouvez changer le fichier docker-compose.dev.yml'
    loginfo '⏳ en attente que vous confirmiez le changement'
    loginfo 'Appuyer sur la touche Entree de votre clavier pour cela'
    read -r

    loginfo 'Lancement service db'
    ${DOCKER_COMPOSE} up -d db_test
    loginfo '⏳ on attend que postgres se lance'
    sleep 30
    loginfo 'Copie du fichier dumptest.sql dans le container db'
    docker cp ${PATHDUMPTEST} ${CONTAINER_DBTEST_NAME}:/var/lib/postgresql/data/dumptest.sql
    loginfo 'Import des données...'
    docker exec -it ${CONTAINER_DBTEST_NAME} psql -U ${POSTGRES_USERTEST} -d ${POSTGRES_DBTEST} -f /var/lib/postgresql/data/dumptest.sql
fi
