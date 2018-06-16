<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class ProjectConfig
{
    public $mode;
    public $branchCode;
    public $definitionsFile;

    public function __construct(string $branchCode)
    {
        if (empty($branchCode)) {
            throw new \InvalidArgumentException('Branch code cannot be empty');
        }
        $this->branchCode = $branchCode;
    }
}