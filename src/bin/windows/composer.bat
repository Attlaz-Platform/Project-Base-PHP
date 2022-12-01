@ECHO OFF
docker run --rm -it --init -v %cd%:/var/attlaz -w /var/attlaz attlaz/php:8.1 composer %*
