@ECHO OFF
docker run --rm -it -w /var/attlaz -v %cd%:/var/attlaz hq.attlaz.com:2498/php7_4:1.1.0 php vendor/bin/grumphp run
