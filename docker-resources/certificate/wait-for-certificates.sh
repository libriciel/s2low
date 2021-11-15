#! /bin/bash

maxWaitingCycles=20
waitingCycles=0

until [ -n "$(ls -A /etc/s2low/ssl/validca/)" ]
do
  if [ "$waitingCycles" -eq "$maxWaitingCycles" ]
  then
  echo "$waitingCycles/$maxWaitingCycles /etc/s2low/ssl/validca empty : Attente trop longue"
  exit 1
  fi
echo "$waitingCycles/$maxWaitingCycles /etc/s2low/ssl/validca empty : waiting for certificates to be retrieved...";
sleep 10
((waitingCycles+=1))
done

echo "$waitingCycles/$maxWaitingCycles /etc/s2low/ssl/validca retrieved"
exit 0