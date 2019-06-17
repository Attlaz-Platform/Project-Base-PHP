<?php
declare(strict_types=1);

use Attlaz\Project\App\Environment;

//if (!defined('STDIN')) {
//    define('STDIN', fopen('php://stdin', 'rb'));
//}
//if (!defined('STDOUT')) {
//    define('STDOUT', fopen('php://stdout', 'wb'));
//}
//if (!defined('STDERR')) {
//    define('STDERR', fopen('php://stderr', 'wb'));
//}
if (!defined('STDOUT')) {
    define('STDOUT', fopen('php://output', 'wb'));
}

return [
    \Psr\Log\LoggerInterface::class => \DI\factory(function (Environment $environment, \DI\Container $container) {
        $loggerName = 'Attlaz';
        if ($environment->isInitialized()) {
            $loggerName = "Attlaz Project " . $environment->getProject()->name . ' (' . $environment->getProjectEnvironment()->name . ')';
        }

        $logger = new \Attlaz\Project\Logger\Logger($loggerName);

        $ignoreDirectories = [
            '/var/attlaz/',
            '/var/attlaz/project/vendor/attlaz/project/src',
        ];
        $introspectionProcessor = new \Monolog\Processor\IntrospectionProcessor(\Monolog\Logger::DEBUG, $ignoreDirectories);
        $logger->pushProcessor($introspectionProcessor);

        /**
         * Log to stream
         */

        if ($environment->cli_log_verbose) {
            $format = '%level_name%: %message% [%datetime%]' . \PHP_EOL . '   %context%' . \PHP_EOL . '%extra%' . \PHP_EOL;
        } else {
            $format = '%level_name%: %message% [%datetime%]' . \PHP_EOL . \PHP_EOL . \PHP_EOL;
        }

        $streamHandler = new \Monolog\Handler\StreamHandler(STDOUT, $environment->cli_log_level);
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
            $apiLogHandler = new \Attlaz\Project\Logger\ApiHandler($container->get(\Attlaz\Client::class));
            $formatter = new \Attlaz\Project\Logger\Formatter();
            $apiLogHandler->setFormatter($formatter);
            $logger->pushHandler($apiLogHandler);
        }
        /**
         * Log fatal errors
         */
        \Monolog\ErrorHandler::register($logger);

        return $logger;
    }),
    \Attlaz\Client::class           => \DI\factory(function (Environment $environment) {
        return new \Attlaz\Client($environment->api_endpoint, $environment->api_client_id, $environment->api_client_secret);
    }),

    \Psr\SimpleCache\CacheInterface::class => \DI\factory(function (
        \Attlaz\Project\Cache\CacheManager $cacheManager
    ) {
        return $cacheManager->getCache();
    }),

];
