#!/bin/sh

if [ -z $1 -o -z $2 ]; then
	echo "Arguments manquants"
	exit
fi

if [ -z $3 ]; then
        alias="tedetis"
else
        alias=$3
fi

jarpath=$1
pass=$2

for jar in $jarpath/*.jar; do
	destjar=`dirname $jar`/s`basename $jar`
	jarsigner -keystore /opt/tomcat/keystore.jks -storepass $pass -signedjar $destjar $jar $alias
	
	if [ $? -eq 0 ]; then
		rm $jar && mv $destjar $jar
	else
		echo "Erreur de signature : $jar"
	fi
done
