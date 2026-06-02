#!/bin/bash

CERT_DIR="/data/certificates/_validca"

/usr/bin/curl -s https://validca.libriciel.fr/retrieve-validca.sh | /bin/bash -s "$CERT_DIR"

# OpenSSL 3.x (alpine) skips PEM files containing multiple certificates during c_rehash
# Split them into individual certificate files
for dir in "$CERT_DIR"/validca "$CERT_DIR"/validcargs; do
    [ -d "$dir" ] || continue
    for pem in "$dir"/*.pem; do
        [ -f "$pem" ] || continue
        count=$(grep -c 'BEGIN CERTIFICATE' "$pem" 2>/dev/null) || count=0
        if [ "$count" -gt 1 ]; then
            base=$(basename "$pem" .pem)
            awk -v dir="$dir" -v base="$base" \
                '/BEGIN CERTIFICATE/{n++} {print > dir "/" base "-split" n ".pem"}' "$pem"
            rm "$pem"
        fi
    done
    openssl rehash "$dir"
done
