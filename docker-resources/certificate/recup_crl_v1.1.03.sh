#!/bin/bash
#version 1.0
#Création du script
#version 1.1
#ajout d'un controle sur les fichiers telecharge via wget
#ajout d'un controle sur la variable MD5
#version 1.1.01
#les noms des fichiers à recuperer peuvent être fourni en argument
#version 1.1.02
#les fichiers validca.tgz et validca.md5sum sont definis par defaut
#verison 1.1.03
#le dossier de stockage du validca est defini par defaut et peut être surcharge via le premier argument $1

DIR="/etc/apache2/ssl"
VALIDCATGZ=validca.tgz
VALIDCAMD5=validca.md5sum

VERSIONSSL=`openssl version | awk '{print $2}' | cut -c1`
if [ $VERSIONSSL -ge 1 ]; then
        VALIDCATGZ=validca_1.0.tgz
        VALIDCAMD5=validca_1.0.md5sum
fi

if [ -e $1 ]; then
	DIR=$1
fi

cd $DIR

if [ -e $DIR/$VALIDCATGZ ]
then
	echo "Suppression de $VALIDCATGZ"
	rm $DIR/$VALIDCATGZ
fi

if [ -e /tmp/$VALIDCAMD5 ]
then
	echo "Suppression de $VALIDCAMD5"
	rm /tmp/$VALIDCAMD5
fi

/usr/bin/wget --no-proxy -q http://crl.adullact.org/$VALIDCATGZ

cd /tmp
/usr/bin/wget --no-proxy -q http://crl.adullact.org/$VALIDCAMD5

if [ ! -e $DIR/$VALIDCATGZ ]
then
	echo "Le fichier $DIR/$VALIDCATGZ n'existe pas"
	exit;
fi

if [ ! -e /tmp/$VALIDCAMD5 ]
then
	echo "Le fichier /tmp/$VALIDCAMD5 n'existe pas"
        exit;
fi

MD5=`md5sum $DIR/$VALIDCATGZ | awk '{print $1}'`
echo $MD5

if [ -z $MD5 ]
then
	echo "PROBLEME MD5SUM null"
	exit;
fi

if [ $MD5 != `cat /tmp/$VALIDCAMD5` ]
then
	echo "PROBLEME MD5SUM DIFFERENT DE CELUI TELECHARGE";
	exit;
fi

if [ -e $DIR/validca-old ]
then
	rm -r $DIR/validca-old
fi

if [ -e $DIR/validca ]
then
	mv $DIR/validca $DIR/validca-old
fi

cd $DIR
tar -xzf $DIR/$VALIDCATGZ
