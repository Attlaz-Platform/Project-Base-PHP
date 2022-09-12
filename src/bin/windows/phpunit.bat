@ECHO OFF
REM TODO: check if phpunit file exits
docker run --rm -it --init -v %cd%:/var/attlaz -w /var/attlaz hq.attlaz.com:2498/php7_4:1.1.0 php vendor/bin/phpunit --configuration phpunit.xml %*
