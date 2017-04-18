# Attlaz

Base Attlaz Application

## Getting Started

### Prerequisites

Install docker and docker-compose

Build images
docker build --no-cache -t attlaz/worker:0.0.1 .

## Non Swarm Mode (Debug)

Start docker containers

```
docker-compose up --build --d
```


Start Queue (run as admin)
```
docker-compose up queue
```
(admin panel is available at localhost:32769)
Start API
```
docker-compose up api
```

Start one worker
```
docker-compose run worker php /var/attlaz/worker/run.php
```

Kill environment

```
docker-compose kill
```



## Swarm mode
Start swarm
```
docker swarm init
docker stack deploy --compose-file=docker-compose.yml ATTLAZ
```
Scaling workers
```
docker service scale ATTLAZ_worker=10 --detach=false
```
Stop swarm
```
docker swarm leave --force
```

## Built With

* [RabbitMQ](https://www.rabbitmq.com/) - open source message broker

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Authors

* **Stijn Duynslaeger** - *Initial work* - [PurpleBooth](https://github.com/Echron)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details