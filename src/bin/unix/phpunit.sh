@ECHO OFF
#REM TODO: check if phpunit file exits
docker run --rm -it --init -v $(pwd):/var/attlaz -w /var/attlaz attlaz/php:8.1 php vendor/bin/phpunit --configuration phpunit.xml %*
