# Attlaz

Base Attlaz Application

## Getting Started



### Prerequisites

Install docker and docker-compose

### Installing

Start docker containers

```
docker-compose up -build -d
```

And repeat

```
until finished
```

End with an example of getting some data out of the system or using it for a little demo

## Running the tests

Run unit tests inside docker container:

```
docker-compose exec app /var/www/app/vendor/bin/phpunit -c /var/www/app/phpunit.xml
```

```
docker-compose exec app php /var/www/app/run.php worker:start
```

```
docker-compose exec app php /var/www/app/run.php manager:start
```

### Break down into end to end tests

Explain what these tests test and why

```
Give an example
```

### And coding style tests

Explain what these tests test and why

```
Give an example
```

## Deployment

Add additional notes about how to deploy this on a live system

## Built With

* [RabbitMQ](https://www.rabbitmq.com/) - open source message broker

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Authors

* **Stijn Duynslaeger** - *Initial work* - [PurpleBooth](https://github.com/Echron)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details