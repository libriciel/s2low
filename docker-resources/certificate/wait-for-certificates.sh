#! /bin/bash

until [ -n "$(ls -A /etc/s2low/ssl/validca/)" ]
do
echo "/etc/s2low/ssl/validca empty : waiting fo certificats-recuperateurs.php to be launched...";
sleep 5
done

echo "/etc/s2low/ssl ok"