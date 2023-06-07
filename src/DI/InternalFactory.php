<?php

declare(strict_types=1);

namespace Attlaz\Project\DI;

use Attlaz\AttlazMonolog\Formatter\AttlazFormatter;
use Attlaz\AttlazMonolog\Handler\AttlazHandler;
use Attlaz\Client;
use Attlaz\Model\Log\LogStreamId;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Logger\Logger;
use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Psr\Log\LoggerInterface;

class InternalFactory
{
    public static function getLogger(Environment $environment, Client $client): LoggerInterface
    {
        $loggerName = 'Attlaz';
        if ($environment->isInitialized()) {
            $projectName = $environment->getProject()->name;
            $projectEnvironmentName = $environment->getProjectEnvironment()->name;

            $loggerName = "Attlaz Project " . $projectName . ' (' . $projectEnvironmentName . ')';
        }

        $logger = new Logger($loggerName);

        //        $skipClassesPartials = [
        //            'Attlaz\\Project\\Logger',
        //            //            '/var/attlaz/',
        //            //            '/var/attlaz/project/vendor/attlaz/project',
        //            //            '/var/attlaz/project/vendor/attlaz/project/src',
        //        ];
        //   $introspectionProcessor = new IntrospectionProcessor(\Monolog\Logger::DEBUG, $skipClassesPartials);
        //    $logger->pushProcessor($introspectionProcessor);

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

        //        $container->set('attlaz_streamhandler', $streamHandler);


        $logger->pushHandler($streamHandler);

        /**
         * Color mode for local development
         */
        if (PHP_SAPI === 'cli') {
            //TODO: only show colors when in developer mode AND local mode
            //TODO: add "verbose" and "non-verbose" mode
            if (class_exists('\Bramus\Monolog\Formatter\ColoredLineFormatter')) {
                $formatter = new ColoredLineFormatter(null, $format, null, false, true);
            } else {
                $formatter = new LineFormatter($format, null, false, true);
            }
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
            $logStreamId = new LogStreamId('environment:' . $environment->getProjectEnvironment()->id);
            $apiLogHandler = new AttlazHandler($client, $logStreamId, Level::Info);
            $formatter = new AttlazFormatter();
            $apiLogHandler->setFormatter($formatter);
            $logger->pushHandler($apiLogHandler);
        }
        /**
         * Log fatal errors
         */
        ErrorHandler::register($logger);

        return $logger;
    }

    private static ?Client $client = null;

    public static function getClient(Environment $environment): Client
    {
        if (self::$client === null) {
            self::$client = new Client($environment->api_client_id, $environment->api_client_secret);
            self::$client->setEndPoint($environment->api_endpoint);
        }

        return self::$client;
    }


}
