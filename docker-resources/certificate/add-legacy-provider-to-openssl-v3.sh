#! /bin/bash

set -ex

# see https://github.com/openssl/openssl/issues/13180


sed -i '/^default = default_sect/a legacy = legacy_sect' /etc/ssl/openssl.cnf
sed -i '/^\[default_sect\]/a activate = 1' /etc/ssl/openssl.cnf


echo "
[legacy_sect]
activate = 1
" >>  /etc/ssl/openssl.cnf
