<?php
declare(strict_types=1);

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Cache\CacheManager;
use Attlaz\Project\Logger\ApiHandler;
use Attlaz\Project\Logger\Formatter;
use Attlaz\Project\Logger\Logger;
use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use function DI\factory;

//if (!defined('STDIN')) {
//    define('STDIN', fopen('php://stdin', 'rb'));
//}
//if (!defined('STDOUT')) {
//    define('STDOUT', fopen('php://stdout', 'wb'));
//}
//if (!defined('STDERR')) {
//    define('STDERR', fopen('php://stderr', 'wb'));
//}
//if (!defined('STDOUT')) {
//    define('STDOUT', fopen('php://output', 'wb'));
//}

return [
    LoggerInterface::class => factory(function (Environment $environment, Container $container) {
        $loggerName = 'Attlaz';
        if ($environment->isInitialized()) {
            $projectName = $environment->getProject()->name;
            $projectEnvironmentName = $environment->getProjectEnvironment()->name;

            $loggerName = "Attlaz Project " . $projectName . ' (' . $projectEnvironmentName . ')';
        }

        $logger = new Logger($loggerName);

        $ignoreDirectories = [
            '/var/attlaz/',
            '/var/attlaz/project/vendor/attlaz/project/src',
        ];
        $introspectionProcessor = new IntrospectionProcessor(\Monolog\Logger::DEBUG, $ignoreDirectories);
        $logger->pushProcessor($introspectionProcessor);

        /**
         * Log to stream
         */

        if ($environment->cli_log_verbose) {
            $format = '%level_name%: %message% [%datetime%]' . \PHP_EOL;
            $format .= '   %context%' . \PHP_EOL . '%extra%' . \PHP_EOL;
        } else {
            $format = '%level_name%: %message% [%datetime%]' . \PHP_EOL . \PHP_EOL . \PHP_EOL;
        }

        $streamHandler = new StreamHandler(STDOUT, $environment->cli_log_level);

        $container->set('attlaz_streamhandler', $streamHandler);

        $logger->pushHandler($streamHandler);

        /**
         * Color mode for local development
         */
        if (PHP_SAPI === 'cli') {
            //TODO: only show colors when in developer mode AND local mode
            //TODO: add "verbose" and "non-verbose" mode
            $formatter = new Bramus\Monolog\Formatter\ColoredLineFormatter(null, $format);
            //  $formatter = new \Monolog\Formatter\LineFormatter($format);
            $formatter->allowInlineLineBreaks(true);

            if ($environment->cli_log_stacktrace) {
                $formatter->includeStacktraces(true);
            }
            $streamHandler->setFormatter($formatter);
        }

        /**
         * Log to API
         */
        if ($environment->isInitialized()) {
            $apiLogHandler = new ApiHandler($container->get(Client::class), \Monolog\Logger::INFO);
            $formatter = new Formatter();
            $apiLogHandler->setFormatter($formatter);
            $logger->pushHandler($apiLogHandler);
        }
        /**
         * Log fatal errors
         */
        \Monolog\ErrorHandler::register($logger);

        return $logger;
    }),
    Client::class          => factory(function (Environment $environment) {
        return new Client($environment->api_endpoint, $environment->api_client_id, $environment->api_client_secret);
    }),

    CacheInterface::class => factory(function (
        CacheManager $cacheManager
    ) {
        return $cacheManager->getCache();
    }),

];
