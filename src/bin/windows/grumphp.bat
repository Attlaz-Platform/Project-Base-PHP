@ECHO OFF
docker run --rm -it -w /var/attlaz -v %cd%:/var/attlaz hq.attlaz.com:2498/attlaz_worker:1.4.21 php vendor/bin/grumphp run
