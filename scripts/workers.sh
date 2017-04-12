#!/bin/bash
mkdir -p /var/www/app/log

if [ $# -ne 1 ]; then
    echo "$0: Invalid argument (usage: workers <amount>)"
    exit 1
fi
count=$1

for ((i=1;i<=$count;i++)); do
  echo "Start worker $i"
  php ../src/run.php worker:start "Consumer $i" &>/dev/null &
done
