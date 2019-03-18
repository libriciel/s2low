#!/bin/bash

function main()
{
    local SLEEP_SECONDS=60
    while [[ 0 == 0 ]]; do
        monitor_from_file $*
        sleep $SLEEP_SECONDS
    done
}

function monitor_vpn_ip_port()
{
    local CONN_NAME=$1
    local IP=$2
    local PORT=$3

    nc -w 10 -z $IP $PORT || ( \
        echo "$IP $PORT did not respond, resetting connection $CONN_NAME"; \
        ipsec down $CONN_NAME; ipsec up $CONN_NAME; )
}

function monitor_from_file()
{
    local FILE=$1
    echo "$FILE"
    if [[ ! -e $FILE ]]; then
        echo "Can not find file $FILE."
        return 1
    fi

    # load the file into memory. Hope it's not too big. :)
    # -t strips out the newlines on each line.
    mapfile -t MYARRAY < $FILE
    # init local variable to contain the current connection name.
    local CONN=
    for LINE in "${MYARRAY[@]}"; do
        # Skip over any lines that have the comment at the very beginning.
        if [[ $LINE =~ ^\# ]]; then continue

        # Look for a line that looks like this which defines a VPN connection:
        # conn CONNECTION-NAME
        elif [[ $LINE =~ ^conn[\ ]  ]]; then
            # extract the part after the "conn " to get the name.
            CONN=`echo $LINE | sed 's/^conn //'`

        # Look for a line where we have the commented 'monitor' keyword.
        # Example:         #monitor 172.17.105.80 9898
        elif [[ $LINE =~ \#monitor ]]; then
            # Remove everything from the beginning up to and including the "#monitor "
            IP_PORT=`echo $LINE | sed 's/^.*#monitor //'`
            printf "`date` monitoring $CONN \t $IP_PORT\n"
            # IP_PORT should be space delimited and hence should work as separate parameters.
            monitor_vpn_ip_port $CONN $IP_PORT

        # if we have a blank line, that ends any connection configuration.
        elif [[ $LINE =~ ^$ ]]; then
            CONN=
        fi
    done
}

echo "Depart surveillance ipsec"
# now start running the script by calling main() with all parameters.
main $*
