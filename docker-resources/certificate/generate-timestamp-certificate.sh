#! /bin/bash

SITE_HOST_NAME=$1
PRIVKEY_PATH=$2
CERTIFICATE_PATH=$3


if [ -z ${CERTIFICATE_PATH} ]
then
echo "Usage $0 hostname key_path certificate_path"
echo "Génère une clé et un certificat auto-signé utilisable pour générer des jetons d'horodatage"
exit -1
fi

SCRIPT_BASE=$(dirname $0)

V3_EXT_PATH="/tmp/$$_timestamp.ext"

cat <<EOF >${V3_EXT_PATH}
extendedKeyUsage = critical,timeStamping
EOF

CSR_TEMP_PATH=/tmp/$$_csr.pem

openssl req  \
        -new \
        -newkey rsa:4096 \
        -days 3650 \
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
        -days 3650 \
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
