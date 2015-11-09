#!/bin/bash
# abet1-daemon - init.d script for ABET1 service
#  install as /etc/init.d/abet1

### BEGIN INIT INFO
# Provides:           abet1-daemon
# Required-Start:     $remote_fs
# Required-Stop:      $remote_fs
# Default-Start:      2 3 4 5
# Default-Stop:       0 1 6
# Short-Description:  ABET1 Service
### END INIT INFO

PIDFILE=/var/run/abet1-daemon.pid

case "$1" in
start)
    # run the daemon; pass it the pidfile; the process will store its PID in
    # this file so that we can look it up later
    php /usr/lib/abet1/abet1-daemon.php "$PIDFILE"
;;
stop)
    # lookup the process from the file
    PID=`cat $PIDFILE`
    if [ -z $PID ]; then
        echo "Cannot find PID-file for daemon"
    else
        kill $PID
        echo "Sent TERM signal to daemon"
    fi
;;
status)
    # see if the PID file exists for the service
    if [ -f $PIDFILE ]; then
        echo "The PID file exists for the service"
    else
        echo "The PID file does not exist for the service"
    fi
;;
esac
