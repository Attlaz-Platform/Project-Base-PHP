#!/usr/bin/env bash
docker run --rm -it --init -v $(pwd):/var/attlaz -w /var/attlaz attlaz/php:8.1 %*
