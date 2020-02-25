<?php

declare(strict_types=1);

namespace Attlaz\Project\Setup;

use Echron\Tools\FileSystem;

class Install
{
    public static function run()
    {
        echo 'Setup project';
        try {
            self::copyBinFiles();
        } catch (\Throwable $ex) {
            throw new \Exception('Unable to run setup: ' . $ex->getMessage());
        }
    }

    private static function copyBinFiles(): void
    {
        // TODO: how to make sure the bin directory is not in git without modifiying the .gitignore file to avoid bad
        // changes to that file
        $sourceBinDirectoryPath = realpath(dirname(__FILE__)) . \DIRECTORY_SEPARATOR . 'bin';

        $destinationBinDirectoryPath = FileSystem::joinPath(dirname(__FILE__), '..', '..', '..', '..', '..', 'bin');
        $destinationBinDirectoryPath = realpath($destinationBinDirectoryPath);

        if (!\is_string($destinationBinDirectoryPath) || !FileSystem::dirExists($destinationBinDirectoryPath)) {
            FileSystem::createDir($destinationBinDirectoryPath, true);
        }

        FileSystem::copyDirectory($sourceBinDirectoryPath, $destinationBinDirectoryPath, true);
    }
}
