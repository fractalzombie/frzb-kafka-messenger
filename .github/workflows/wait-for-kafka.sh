#! /bin/bash
END=$((SECONDS+60))
SLEEP_TIME_BEFORE_RECONNECT=5

while [ $SECONDS -lt $END ]; do
    if echo dump | nc localhost 2181 | grep broker ; then
        exit 0
    else
  echo "$(date) - Trying connect to kafka:2181"
    fi
  sleep $SLEEP_TIME_BEFORE_RECONNECT
done

echo "$(date) - Connection to kafka:2181 established"
