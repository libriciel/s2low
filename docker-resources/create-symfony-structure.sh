#! /bin/bash

if [ ! -d /var/www/s2low/.env ]
   then touch /var/www/s2low/.env;
fi;

if [ ! -d /var/www/s2low/var ]
   then
     mkdir /var/www/s2low/var;
     mkdir /var/www/s2low/var/log;
     mkdir /var/www/s2low/var/cache;
     chown -R www-data:www-data /var/www/s2low/var;
fi;

if [ ! -d /var/www/s2low/migrations ]
   then
     mkdir /var/www/s2low/migrations;
fi;
