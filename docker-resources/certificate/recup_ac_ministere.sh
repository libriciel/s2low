#!/bin/bash

if [ -z "$1" ] || [ -z "$2" ];
then
	echo "Syntaxe : ./recup_ac_ministere.sh CertPath CAPath";
	exit 1;
fi


if [[ $1 != /* ]] || [[ $1 != /* ]];
then
	echo "Les répertoires doivent être absolus";
	exit 2;
fi

if [ ! -d "$1" ] || [ ! -d "$2" ];
then
	echo "Les deux répertoires doivent exister";
	exit 3;
fi

DIR1=$1
DIR2=$2

cd "$DIR1" || exit 4;

echo "Copie des certificats dans $DIR1"

wget https://ressources.libriciel.fr/s2low/certificat-mi-chaine.pem
wget https://ressources.libriciel.fr/s2low/ac-racine-mi.pem
wget https://ressources.libriciel.fr/s2low/serveur-1e.pem

cd "$DIR2" || exit 5;

echo "Creation des liens symboliques dans $DIR2"
ln -sfn "$DIR1"/ac-racine-mi.pem
ln -sfn "$DIR1"/serveur-1e.pem
c_rehash .
