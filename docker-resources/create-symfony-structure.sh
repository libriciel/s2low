#! /bin/bash

if [ ! -d /var/www/s2low/.env ]
   then touch /var/www/s2low/.env;
fi;

if [ ! -d /var/www/s2low/var ]
   then
     touch /var/www/s2low/var;
     chown -R www-data:www-data /var/www/s2low/var;
fi;