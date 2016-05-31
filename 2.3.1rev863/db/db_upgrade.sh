#!/bin/bash

PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin

tedetis_db="tedetis"

fix_perms() {
	if [ -z $1 ]; then
		USER="tedetis"
	else
		USER=$1
	fi

	REL=`sudo su - postgres "psql -c '\dt' tedetis | grep table | cut -d'|' -f2"`
	SEQ=`sudo su - postgres "psql -c '\ds' tedetis | grep quence | cut -d'|' -f2"`
	REL=`echo $REL|sed 's/ /,/g'`
	SEQ=`echo $SEQ|sed 's/ /,/g'`
	SQL="GRANT SELECT,INSERT,DELETE,UPDATE ON ${REL} TO $USER"
	echo "Fixing relations permissions:"
	echo ${SQL}
	su - postgres "psql -c \"${SQL}\" tedetis"
	SQL="GRANT SELECT,INSERT,DELETE,UPDATE ON ${SEQ} TO $USER"
	echo "Fixing sequence permissions:"
	echo ${SQL}
	su - postgres "psql -c \"${SQL}\" tedetis"
}

apply_upgrades() {
	echo "Applying $1"
	su - postgres psql $tedetis_db < $1
	if [ ! $? ]; then
		echo "$1: error occured during upgrade"
		exit 1
	else
		echo "$1: done"
	fi
}

if [ -z $1 -o -z $2 ]; then
	echo "Usage: $0 from_revision to_revision [database_user]"
	echo "with both revisions of the form: 1.0.2"
	exit 1
fi

from=$1
to=$2

if [ -z $3 ]; then
	USER="tedetis"
else
	USER=$3
fi

if [ ! -d ./upgrades ]; then
	echo "Can't find upgrades directory. Exiting."
	exit 1
fi

# Core upgrades
echo "Processing core upgrades"
for i in `find ./upgrades/ -type f -name 'tedetis_core_'$from'-to-'$to'.sql' -print`; do
	apply_upgrades $i
done

# Modules upgrades
echo "Processing modules upgrades"
for i in `find ./upgrades/ -type f -name 'tedetis_module_*_'$from'-to-'$to'.sql' -print`; do
	apply_upgrades $i
done

fix_perms $USER

exit 0