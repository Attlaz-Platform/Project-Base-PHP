#!/usr/bin/env bash
docker run --rm -it --init -v $(pwd):/var/attlaz -w /var/attlaz prooph/composer:7.2 %*
