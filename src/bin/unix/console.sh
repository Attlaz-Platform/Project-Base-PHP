#!/usr/bin/env bash
docker run --rm -it --init -v $(pwd):/var/attlaz -w /var/attlaz hq.attlaz.com:2498/php7_4:1.1.0 php bin/console %*
