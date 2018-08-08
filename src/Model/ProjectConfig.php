<?php


namespace Attlaz\Project\Model;

use Attlaz\Project\App\Config;
/** @deprecated   */
class ProjectConfig extends Config
{


    public function __construct(string $branch)
    {


        $this->branch = $branch;

        $this->api_endpoint = 'https://api3.attlaz.com';
        $this->api_client_id = 'democlient';
        $this->api_client_secret = 'democlientsecret';

        $this->storage = 'mongodb://REDACTED';
    }


}