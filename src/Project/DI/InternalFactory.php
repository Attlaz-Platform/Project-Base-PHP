<?php

declare(strict_types=1);

namespace Attlaz\Project\DI;

use Attlaz\Client;
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
         * Logging to the API is attached per flow run, by CommandManager, using that run's log
         * stream. There is no handler here because there is no stream to send to outside a run: this
         * used to attach one addressed to `environment:<id>`, an identifier form retired in May 2025,
         * and the API has discarded those writes since September 2025 without storing them.
         */
        /**
         * Log fatal errors
         */
        ErrorHandler::register($logger);

        return $logger;
    }

    private static Client|null $client = null;

    public static function getClient(Environment $environment): Client
    {
        if (self::$client === null) {

            $client = new Client();
            $client->setEndPoint($environment->api_endpoint);
            if ($environment->api_client_id !== null && $environment->api_client_secret !== null) {
                $client->authWithClient($environment->api_client_id, $environment->api_client_secret);
            } elseif ($environment->api_client_token !== null) {
                $client->authWithToken($environment->api_client_token);
            }
            self::$client = $client;
        }

        return self::$client;
    }


}
