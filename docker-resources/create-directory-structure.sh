#! /bin/bash

# Cr�ation de l'arborescence du workspace
mkdir -p /data/tdt-workspace/actes && \
mkdir -p /data/tdt-workspace/helios && \
mkdir -p /data/tdt-workspace/mail && \
mkdir -p /data/tdt-workspace/uploads && \
mkdir -p /data/tdt-workspace/dia && \
mkdir -p /data/tdt-workspace/actes/uploads && \
mkdir -p /data/tdt-workspace/actes/batchs && \
mkdir -p /data/tdt-workspace/actes/response_tmp && \
mkdir -p /data/tdt-workspace/actes/response_error && \
mkdir -p /data/tdt-workspace/helios/sending-tmp && \
mkdir -p /data/tdt-workspace/helios/sending && \
mkdir -p /data/tdt-workspace/helios/response && \
mkdir -p /data/tdt-workspace/helios/response_tmp && \
mkdir -p /data/tdt-workspace/helios/response_backup && \
mkdir -p /data/tdt-workspace/helios/response_error && \
mkdir -p /data/tdt-workspace/helios/temp && \
mkdir -p /data/tdt-workspace/helios/ocre && \
mkdir -p /data/tdt-workspace/logs-export && \
mkdir -p /data/tdt-workspace/uploads/etat_civil && \
mkdir -p /data/log/

chown -R www-data:www-data /data/tdt-workspace/
chown -R www-data:www-data /var/lib/php/session
chown -R www-data:www-data /data/log/