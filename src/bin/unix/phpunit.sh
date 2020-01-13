@ECHO OFF
#REM TODO: check if phpunit file exits
docker run --rm -it --init -v $(pwd):/var/attlaz -w /var/attlaz hq.attlaz.com:2498/attlaz_worker:1.3.4 php vendor/bin/phpunit --configuration phpunit.xml %*
