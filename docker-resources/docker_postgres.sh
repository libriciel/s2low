#!/bin/sh

#cp /var/backup/postgresql.conf $PGDATA/postgresql.conf

sed -i -e"s/#standard_conforming_strings = on/standard_conforming_strings = off/" $PGDATA/postgresql.conf
sed -i -e"s/log_timezone = 'UTC'/log_timezone = 'Europe\/Paris'/" $PGDATA/postgresql.conf
sed -i -e"s/timezone = 'UTC'/timezone = 'Europe\/Paris'/" $PGDATA/postgresql.conf
sed -i -e"s/lc_messages = 'C'/lc_messages = 'fr_FR.UTF-8'/" $PGDATA/postgresql.conf
sed -i -e"s/lc_monetary = 'C'/lc_monetary = 'fr_FR.UTF-8'/" $PGDATA/postgresql.conf
sed -i -e"s/lc_numeric = 'C'/lc_numeric = 'fr_FR.UTF-8'/" $PGDATA/postgresql.conf
sed -i -e"s/lc_time = 'C'/lc_time = 'fr_FR.UTF-8'/" $PGDATA/postgresql.conf
