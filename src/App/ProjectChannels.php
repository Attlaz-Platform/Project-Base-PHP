<?php

namespace Attlaz\Framework\App;


use Echron\DataTypes\CodeCollection;

class ProjectChannels extends CodeCollection
{
    public function addChannel(ProjectChannel $projectChannel)
    {
        $this->add($projectChannel->getCode(), $projectChannel);
    }

}