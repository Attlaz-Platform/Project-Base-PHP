<?php
declare(strict_types=1);

use Attlaz\Project\App\Environment;

return [
    \Psr\Log\LoggerInterface::class => \DI\factory(function (Environment $environment) {
        $logger = new \Attlaz\Project\Logger\Logger("Attlaz Project " . $environment->branch);

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

        //TODO: only show colors when in developer mode AND local mode
        //TODO: add "verbose" and "non-verbose" mode
        $formatter = new Bramus\Monolog\Formatter\ColoredLineFormatter(null, $format);
        //  $formatter = new \Monolog\Formatter\LineFormatter($format);
        $formatter->allowInlineLineBreaks(true);

        if ($environment->cli_log_stacktrace) {
            $formatter->includeStacktraces(true);
        }

        $streamHandler = new \Monolog\Handler\StreamHandler(STDOUT, $environment->cli_log_level);
        $streamHandler->setFormatter($formatter);

        $logger->pushHandler($streamHandler);

        /**
         * Log to API
         */

        $apiClient = new \Attlaz\Client($environment->api_endpoint, $environment->api_client_id, $environment->api_client_secret);
        $apiLogHandler = new \Attlaz\Project\Logger\ApiHandler($apiClient);
        $logger->pushHandler($apiLogHandler);

        /**
         * Log fatal errors
         */
        \Monolog\ErrorHandler::register($logger);

        return $logger;
    }),

    \Psr\SimpleCache\CacheInterface::class => \DI\factory(function (
        \Attlaz\Project\Cache\CacheManager $cacheManager
    ) {
        return $cacheManager->getCache();
    }),

];
