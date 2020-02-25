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
            self::makeExecutable();
        } catch (\Throwable $ex) {
            throw new \Exception('Unable to run setup: ' . $ex->getMessage());
        }
    }

    private static function getVendorBinPath(): string
    {
        return realpath(dirname(__FILE__)) . \DIRECTORY_SEPARATOR . 'bin';
    }

    private static function getProjectBinPath(): string
    {
        return FileSystem::joinPath(dirname(__FILE__), '..', '..', '..', '..', '..', 'bin');
    }

    private static function copyBinFiles(): void
    {
        // TODO: how to make sure the bin directory is not in git without modifiying the .gitignore file to avoid bad
        // changes to that file
        $sourceBinDirectoryPath = self::getVendorBinPath();
        $destinationBinDirectoryPath = self::getProjectBinPath();

        if (!FileSystem::dirExists($destinationBinDirectoryPath)) {
            FileSystem::createDir($destinationBinDirectoryPath, true);
        }

        FileSystem::copyDirectory($sourceBinDirectoryPath, $destinationBinDirectoryPath, true);
    }

    private static function makeExecutable(): void
    {
        $sourceBinDirectoryPath = self::getVendorBinPath();
        $destinationBinDirectoryPath = self::getProjectBinPath();

        $files = [
            $sourceBinDirectoryPath . \DIRECTORY_SEPARATOR . 'console.sh',
            $destinationBinDirectoryPath . \DIRECTORY_SEPARATOR . 'console.sh',
            $destinationBinDirectoryPath . \DIRECTORY_SEPARATOR . 'console',
        ];
        foreach ($files as $file) {
            \chmod($sourceBinDirectoryPath, 0755);
        }
    }
}
