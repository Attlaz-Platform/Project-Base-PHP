#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$task = new \Attlaz\Core\Model\Task('example', ['input' => 12]);

$logger = new \Monolog\Logger('Attlaz');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(STDOUT));

$ex = new \Attlaz\Core\Command\ExecuteTask();
$ex->__invoke($task, $logger);