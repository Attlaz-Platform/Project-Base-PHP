@ECHO OFF
docker run --rm -it --init -v %cd%:/var/attlaz -w /var/attlaz prooph/composer:7.2 %*
