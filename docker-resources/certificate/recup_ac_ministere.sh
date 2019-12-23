#!/bin/bash

DIR1="/etc/apache2/ssl"
DIR2="/etc/ssl/certs/"

if [ -e $1 ]; then
	DIR1=$1
fi

if [ -e $2 ]; then
	DIR2=$2
fi

cd $DIR1

echo "Copie des certificats dans "$DIR1

wget https://ressources.libriciel.fr/s2low/certificat-mi-chaine.pem
wget https://ressources.libriciel.fr/s2low/ac-racine-mi.pem
wget https://ressources.libriciel.fr/s2low/serveur-1e.pem

cd $DIR2

echo "Creation des liens symboliques dans "$DIR2
ln -sfn $DIR1/ac-racine-mi.pem
ln -sfn $DIR1/serveur-1e.pem
c_rehash
