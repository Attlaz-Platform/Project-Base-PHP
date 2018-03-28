<?php
declare(strict_types=1);

$mongoDBConnectionString = 'mongodb://REDACTED';
$mongoDBConnectionString = 'mongodb://REDACTED';
$mongoDBConnectionString = 'mongodb://REDACTED';

$mongoDBUriOptions = [
    'readPreference' => 'nearest',
];

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    \Psr\Log\LoggerInterface::class        => \DI\factory(function (ContainerInterface $c) use ($mongoDBConnectionString, $mongoDBUriOptions) {
        $logger = new \Attlaz\Project\App\Logger("Attlaz Project " . $c->get('branchCode'));

        $format = 'LOG_%level_name%: %message% %context% %extra% [%datetime%]' . \PHP_EOL;

        $runLocal = false;
        $jetbrains = \getenv('JETBRAINS_REMOTE_RUN');
        if ($jetbrains === '1') {
            $runLocal = true;
        }

        if ($runLocal) {
            $formatter = new \Bramus\Monolog\Formatter\ColoredLineFormatter(null, $format);
            $formatter->allowInlineLineBreaks(true);
            $formatter->includeStacktraces(true);
        } else {
            $formatter = new \Monolog\Formatter\LineFormatter($format);
            $formatter->allowInlineLineBreaks(false);
        }

        $streamHandler = new \Monolog\Handler\StreamHandler(STDOUT, \Monolog\Logger::DEBUG);
        $streamHandler->setFormatter($formatter);

        $logger->pushHandler($streamHandler);

        /**
         * Log to MongoDB
         */
        $mongoDBClient = new \MongoDB\Client($mongoDBConnectionString, $mongoDBUriOptions);

        $mongoDBHandler = new \Monolog\Handler\MongoDBHandler($mongoDBClient, 'attlaz', 'log');
        $mongoDBHandler->setFormatter(new Monolog\Formatter\NormalizerFormatter('Y-m-d\TH:i:s.v\Z'));

        $logger->pushHandler($mongoDBHandler);

        \Monolog\ErrorHandler::register($logger);

        return $logger;
    }),
    \Psr\SimpleCache\CacheInterface::class => \DI\factory(function (ContainerInterface $c, LoggerInterface $logger) use ($mongoDBConnectionString, $mongoDBUriOptions) {
        $manager = new \MongoDB\Driver\Manager($mongoDBConnectionString, $mongoDBUriOptions);

        $collection = new \MongoDB\Collection($manager, 'attlaz', $c->get('branchCode') . '_cache');

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
    \Echron\IO\Client\Cache::class         => \DI\factory(function (ContainerInterface $c) use ($mongoDBConnectionString, $mongoDBUriOptions) {
        $manager = new \MongoDB\Driver\Manager($mongoDBConnectionString, $mongoDBUriOptions);

        $collection = new \MongoDB\Collection($manager, 'attlaz', $c->get('branchCode') . '_storage');

        $pool = new \Cache\Adapter\MongoDB\MongoDBCachePool($collection);

        $cacheClient = new \Echron\IO\Client\Cache($pool);

        return $cacheClient;
    }),

];