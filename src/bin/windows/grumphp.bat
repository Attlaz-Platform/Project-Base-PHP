@ECHO OFF
docker run --rm -it -w /var/attlaz -v %cd%:/var/attlaz hq.attlaz.com:2498/attlaz_worker:08-11-19_00h48  php vendor/bin/grumphp run
