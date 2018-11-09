<?php
declare(strict_types=1);

use Attlaz\Project\App\Config;
use Psr\Log\LoggerInterface;

return [
    \Psr\Log\LoggerInterface::class => \DI\factory(function (Config $config) {
        $logger = new \Attlaz\Project\Logger\Logger("Attlaz Project " . $config->branch);

        $ignoreDirectories = [
            '/var/attlaz/',
            '/var/attlaz/project/vendor/attlaz/project/src',
        ];
        $introspectionProcessor = new \Monolog\Processor\IntrospectionProcessor(\Monolog\Logger::DEBUG, $ignoreDirectories);
        $logger->pushProcessor($introspectionProcessor);

        /**
         * Log to stream
         */

        $format = 'LOG_%level_name%: %message% %context% %extra% [%datetime%]' . \PHP_EOL;

        //TODO: only show colors when in developer mode AND local mode
        $formatter = new Bramus\Monolog\Formatter\ColoredLineFormatter(null, $format);
        //  $formatter = new \Monolog\Formatter\LineFormatter($format);
        $formatter->allowInlineLineBreaks(false);
        $formatter->includeStacktraces(true);

        $streamHandler = new \Monolog\Handler\StreamHandler(STDOUT, \Monolog\Logger::DEBUG);
        $streamHandler->setFormatter($formatter);

        $logger->pushHandler($streamHandler);

        /**
         * Log to API
         */

        $apiClient = new \Attlaz\Client($config->api_endpoint, $config->api_client_id, $config->api_client_secret);
        $apiLogHandler = new \Attlaz\Project\Logger\ApiHandler($apiClient);
        $logger->pushHandler($apiLogHandler);

        /**
         * Log fatal errors
         */
        \Monolog\ErrorHandler::register($logger);

        return $logger;
    }),
    \MongoDB\Driver\Manager::class  => \DI\factory(function (Config $config) {
        echo 'get manager' . PHP_EOL;

        return new \MongoDB\Driver\Manager($config->storage, ['readPreference' => 'nearest']);
    }),

    \Psr\SimpleCache\CacheInterface::class          => \DI\factory(function (LoggerInterface $logger, Config $config, \MongoDB\Driver\Manager $manager) {
        echo 'get cache' . PHP_EOL;
        $collection = new \MongoDB\Collection($manager, 'attlaz_cache_' . $config->getBranchNameSafe(), 'default');

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
    \Attlaz\Project\Model\Cache\CacheManager::class => \DI\factory(function (LoggerInterface $logger, Config $config, \MongoDB\Driver\Manager $manager) {
        echo 'get cachemanager' . PHP_EOL;
        $cacheManager = new \Attlaz\Project\Model\Cache\CacheManager($manager, 'attlaz_cache_' . $config->getBranchNameSafe(), $logger);

        return $cacheManager;
    }),
    \Echron\IO\Client\Cache::class                  => \DI\factory(function (Config $config, \MongoDB\Driver\Manager $manager) {
        echo 'get cacheclient' . PHP_EOL;
        $collection = new \MongoDB\Collection($manager, 'attlaz_cache_' . $config->getBranchNameSafe(), 'storage');

        $pool = new \Cache\Adapter\MongoDB\MongoDBCachePool($collection);

        $cacheClient = new \Echron\IO\Client\Cache($pool);

        return $cacheClient;
    }),

];