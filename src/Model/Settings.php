<?php
declare(strict_types=1);

namespace Attlaz\Core\Model;

use Symfony\Component\Yaml\Yaml;

class Settings
{
    public $queue_job_name;
    public $queue_job_host;
    public $queue_job_port;
    public $queue_job_user;
    public $queue_job_password;

    public static function fromFile(string $file): Settings
    {
        $config = Yaml::parse(file_get_contents($file));

        $settings = new Settings();
        $settings->queue_job_name = $config['queue']['job']['name'];
        $settings->queue_job_host = $config['queue']['job']['host'];
        $settings->queue_job_port = $config['queue']['job']['port'];
        $settings->queue_job_user = $config['queue']['job']['user'];
        $settings->queue_job_password = $config['queue']['job']['password'];

        return $settings;

    }

}