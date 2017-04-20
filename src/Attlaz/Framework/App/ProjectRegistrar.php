<?php
declare(strict_types=1);

namespace Attlaz\Framework\App;

class ProjectRegistrar
{
    private static $project;

    public static function registerProject(Project $project)
    {
        self::$project = $project;
    }

    public static function getProject(): Project
    {
        return self::$project;
    }

}