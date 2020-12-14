#! /bin/bash

SITE_HOST_NAME=$1

CREATED_FILE_PATH=/etc/apache2/ssl

PRIVKEY_PATH=${CREATED_FILE_PATH}/privkey.pem
CERTIFICATE_PATH=${CREATED_FILE_PATH}/fullchain.pem

if [ -z ${SITE_HOST_NAME} ]
then
echo "Usage $0 hostname"
echo "Génère une clé et un certificat auto-signé utilisable dans un navigateur pour du test/développement"
exit -1
fi

SCRIPT_BASE=$(dirname $0)

if [ -f ${PRIVKEY_PATH} ]
then
echo "Le fichier ${PRIVKEY_PATH} existe déjà [PASS]"
exit 0
fi;

if [ -f ${CERTIFICATE_PATH} ]
then
echo "Le fichier ${CERTIFICATE_PATH} existe déjà [ERROR]";
exit -1;
fi;

echo "Le certificat du site n'a pas été trouvé, on en génère un"

V3_EXT_PATH=/tmp/$$_v3.ext
sed "s/%PASTELL_HOST_NAME%/${SITE_HOST_NAME}/" ${SCRIPT_BASE}/v3.ext > ${V3_EXT_PATH}


CSR_TEMP_PATH=/tmp/$$_csr.pem

openssl req  \
        -new \
        -newkey rsa:4096 \
        -days 825 \
        -nodes  \
        -subj "/C=FR/ST=HERAULT/L=MONTPELLIER/O=LIBRICIEL/OU=CERTIFICAT_AUTO_SIGNE/CN=${SITE_HOST_NAME}/emailAddress=test@localhost" \
        -keyout ${PRIVKEY_PATH} \
        -out ${CSR_TEMP_PATH}

if [ $? -ne 0 ]
then
echo "Problème lors de la génération du CSR"
exit -1
fi

openssl x509 \
        -req \
        -days 825 \
        -in ${CSR_TEMP_PATH} \
        -signkey ${PRIVKEY_PATH} \
        -out ${CERTIFICATE_PATH} \
        -extfile ${V3_EXT_PATH}

if [ $? -ne 0 ]
then
echo "Problème lors de la signature du certificat"
exit -1
fi

rm ${CSR_TEMP_PATH}
rm ${V3_EXT_PATH}

chmod 400 ${PRIVKEY_PATH}

echo "Génération de la clé et du certificat terminé"
