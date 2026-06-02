#!/bin/sh

DIRECTORIES="
/data/certificates/actes_truststore
/data/tdt-workspace/actes
/data/tdt-workspace/helios
/data/tdt-workspace/mail
/data/tdt-workspace/uploads
/data/tdt-workspace/dia
/data/tdt-workspace/actes/uploads
/data/tdt-workspace/actes/sans_transaction
/data/tdt-workspace/actes/batchs
/data/tdt-workspace/actes/response_tmp
/data/tdt-workspace/actes/response_error
/data/tdt-workspace/helios/sending-tmp
/data/tdt-workspace/helios/sending
/data/tdt-workspace/helios/response_tmp
/data/tdt-workspace/helios/response
/data/tdt-workspace/helios/response_error
/data/tdt-workspace/helios/orphelins
/data/tdt-workspace/helios/temp
/data/tdt-workspace/helios/ocre
/data/tdt-workspace/logs-export
/data/tdt-workspace/uploads/etat_civil
"

echo "Création des répertoires nécessaires"

for DIRECTORY in $DIRECTORIES
do
    mkdir -p "$DIRECTORY"
done
