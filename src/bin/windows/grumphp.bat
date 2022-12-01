@ECHO OFF
docker run --rm -it -w /var/attlaz -v %cd%:/var/attlaz attlaz/php:8.1 php vendor/bin/grumphp run
