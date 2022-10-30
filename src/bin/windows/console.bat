@ECHO OFF
@REM docker run --rm -it --init -v %cd%:/var/attlaz -w /var/attlaz hq.attlaz.com:2498/php7_4-debug:1.1.0 -d xdebug.scream=true -d xdebug.output_dir=/var/attlaz/profile -d xdebug.mode=profile -d xdebug.use_compression=0 -d xdebug.profiler_output_name=cachegrind.out.%%t bin/console %*
docker run --rm -it --init -v %cd%:/var/attlaz -w /var/attlaz hq.attlaz.com:2498/php8_1:1.0.0 bin/console %*



REM hq.attlaz.com:2498/php8_1:1.0.0

REM docker run --rm -it --init -v %cd%:/var/attlaz -w /var/attlaz hq.attlaz.com:2498/php8_1-debug:1.0.0 -d xdebug.scream=true -d xdebug.output_dir=/var/attlaz/profile -d xdebug.mode=profile -d xdebug.use_compression=0 -d xdebug.profiler_output_name=cachegrind.out.%%t bin/console %*
