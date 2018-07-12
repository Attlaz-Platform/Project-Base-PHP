<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    \Psr\Log\LoggerInterface::class        => \DI\factory(function (ContainerInterface $c) {
        $logger = new \Attlaz\Project\App\Logger("Attlaz Project " . $c->get('branchCode'));

        $format = 'LOG_%level_name%: %message% %context% %extra% [%datetime%]' . \PHP_EOL;


            $formatter = new \Monolog\Formatter\LineFormatter($format);
            $formatter->allowInlineLineBreaks(false);
        $formatter->includeStacktraces(true);

        $introspectionProcessor = new \Monolog\Processor\IntrospectionProcessor(\Monolog\Logger::DEBUG, ['/var/attlaz/project/vendor/attlaz/project/src']);
        $logger->pushProcessor($introspectionProcessor);


        $streamHandler = new \Monolog\Handler\StreamHandler(STDOUT, \Monolog\Logger::DEBUG);
        $streamHandler->setFormatter($formatter);

        $logger->pushHandler($streamHandler);

        /**
         * Log to MongoDB
         */
        $mongoDBConnectionString = $c->get('mongoDBConnectionString');
        $mongoDBUriOptions = $c->get('mongoDBUriOptions');
        $mongoDBClient = new \MongoDB\Client($mongoDBConnectionString, $mongoDBUriOptions);

        $mongoDBHandler = new \Monolog\Handler\MongoDBHandler($mongoDBClient, 'attlaz', 'log');
        $mongoDBHandler->setFormatter(new Monolog\Formatter\NormalizerFormatter('Y-m-d\TH:i:s.v\Z'));

        $logger->pushHandler($mongoDBHandler);

        \Monolog\ErrorHandler::register($logger);

        return $logger;
    }),
    \Psr\SimpleCache\CacheInterface::class => \DI\factory(function (ContainerInterface $c, LoggerInterface $logger) {
        $mongoDBConnectionString = $c->get('mongoDBConnectionString');
        $mongoDBUriOptions = $c->get('mongoDBUriOptions');
        $manager = new \MongoDB\Driver\Manager($mongoDBConnectionString, $mongoDBUriOptions);

        $collection = new \MongoDB\Collection($manager, 'attlaz_cache_' . $c->get('branchCode'), 'default');

        $cachePools = [];

        $mongoDBCache = new \Cache\Adapter\MongoDB\MongoDBCachePool($collection);
        $mongoDBCache->setLogger($logger);
        $cachePools[] = $mongoDBCache;

        $fileCache = new Cache\Adapter\PHPArray\ArrayCachePool(null);
        $cachePools[] = $fileCache;

        $cache = new Attlaz\Project\Model\Cache\FailOverCachePool($cachePools, [
            'skip_on_failure'        => true,
            'remove_pool_on_failure' => true,
        ]);
        $cache->setLogger($logger);

        return $cache;
    }),
    \Attlaz\Project\Model\Cache\CacheManager::class => \DI\factory(function (ContainerInterface $c, LoggerInterface $logger) {
        $mongoDBConnectionString = $c->get('mongoDBConnectionString');
        $mongoDBUriOptions = $c->get('mongoDBUriOptions');
        $manager = new \MongoDB\Driver\Manager($mongoDBConnectionString, $mongoDBUriOptions);

        $cacheManager = new \Attlaz\Project\Model\Cache\CacheManager($manager, 'attlaz_cache_' . $c->get('branchCode'), $logger);

        return $cacheManager;
    }),
    \Echron\IO\Client\Cache::class         => \DI\factory(function (ContainerInterface $c) {
        $mongoDBConnectionString = $c->get('mongoDBConnectionString');
        $mongoDBUriOptions = $c->get('mongoDBUriOptions');
        $manager = new \MongoDB\Driver\Manager($mongoDBConnectionString, $mongoDBUriOptions);

        $collection = new \MongoDB\Collection($manager, 'attlaz_cache_' . $c->get('branchCode'), 'storage');

        $pool = new \Cache\Adapter\MongoDB\MongoDBCachePool($collection);

        $cacheClient = new \Echron\IO\Client\Cache($pool);

        return $cacheClient;
    }),

];