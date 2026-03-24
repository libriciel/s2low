#!/bin/bash

if [ -z "$1" ] || [ -z "$2" ];
then
	echo "Erreur : nombre de paramètre incorrect (2 attendus)";
	echo "Syntaxe : ./recup_ac_ministere.sh CertPath CAPath";
	echo "Télécharge les autorités de certification du ministère de l'intérieur (@ctes) dans CertPath et crée un lien symbolique dans CAPath";
	exit 1;
fi

if [[ $1 != /* ]] || [[ $1 != /* ]];
then
	echo "Erreur : Les chemins des répertoires doivent être absolus";
	exit 2;
fi

if [ ! -d "$1" ] || [ ! -d "$2" ];
then
	echo "Erreur : Les deux répertoires doivent exister";
	exit 3;
fi

DIR1=$1
DIR2=$2

cd "$DIR1" || exit 4;

echo "Copie des certificats dans $DIR1"

wget -N https://ressources.libriciel.fr/s2low/certificat-mi-chaine.pem
wget -N https://ressources.libriciel.fr/s2low/ac-racine-mi.pem
wget -N https://ressources.libriciel.fr/s2low/ac-serveur-auth-v1.pem

cd "$DIR2" || exit 5;

echo "Creation des liens symboliques dans $DIR2"
ln -sfn "$DIR1"/ac-racine-mi.pem
ln -sfn "$DIR1"/ac-serveur-auth-v1.pem
c_rehash .


# echo "Ajout des AC du Ministère dans /etc/ssl/certs"
# cd /etc/ssl/certs
# ln -sfn "$DIR1"/ac-racine-mi.pem
# ln -sfn "$DIR1"/serveur-1e.pem
# c_rehash .
