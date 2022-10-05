<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class Config extends \Attlaz\Model\Config
{
    public $source;

    public static function fromBase(\Attlaz\Model\Config $input): self
    {
        $result = new self();
        foreach ($input as $key => $value) {
            $result->$key = $value;
        }

        return $result;
    }
}
