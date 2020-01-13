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
        $sourceBinDirectoryPath = realpath(dirname(__FILE__)) . \DIRECTORY_SEPARATOR . 'bin';

        $destinationBinDirectoryPath = FileSystem::joinPath(dirname(__FILE__), '..', '..', '..', '..', '..', 'bin');
        $destinationBinDirectoryPath = realpath($destinationBinDirectoryPath);

        if (!\is_string($destinationBinDirectoryPath)) {
            throw new \Exception('Destination directory not found');
        }

        self::copyDirectory($sourceBinDirectoryPath, $destinationBinDirectoryPath);
    }

    private static function copyDirectory(string $src, string $dst): void
    {
        // open the source directory
        $dir = opendir($src);
        if ($dir === false) {
            throw new \Exception('Unable to read source directory');
        }

        // Make the destination directory if not exist
        @mkdir($dst);

        // Loop through the files in source directory
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    // Recursively calling custom copy function
                    // for sub directory
                    self::copyDirectory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }
}
