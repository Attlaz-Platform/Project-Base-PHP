# Attlaz Project #

Base Attlaz Project

```bash
docker run --rm -it -v %cd%:/var/attlaz hq.attlaz.com:2498/attlaz_worker:07-08-18_17h00 php /var/attlaz/test.php
```

# Run GrumPHP

```bash
docker run --rm -it -w /var/attlaz -v %cd%:/var/attlaz hq.attlaz.com:2498/attlaz_worker:08-11-19_00h48 php vendor/bin/grumphp run
```

## GrumPHP installed

- PHPStan https://github.com/phpstan/phpstan

# Run Project

```bash
docker run --rm -it -v %cd%:/var/attlaz -w /var/attlaz hq.attlaz.com:2498/attlaz_worker:07-08-18_17h00 php bin/console %*
```

# Update packages

```bash
docker run -it -v ${PWD}:/app -w /app --rm attlaz/php:8.2 composer update
```
