@ECHO OFF
docker run --rm -it --init -v %cd%:/var/attlaz -w /var/attlaz hq.attlaz.com:2498/attlaz_worker:1.4.6 php bin/console %*
