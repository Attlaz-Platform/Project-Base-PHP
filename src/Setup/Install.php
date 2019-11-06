<?php
declare(strict_types=1);

namespace Attlaz\Project\Setup;

use Echron\Tools\FileSystem;

class Install
{
    public static function run()
    {
        echo 'Running';

        $sourceBinDirectoryPath = realpath(dirname(__FILE__)) . \DIRECTORY_SEPARATOR . 'bin';

        $destinationBinDirectoryPath = FileSystem::joinPath(dirname(__FILE__), '..', '..', '..', '..', '..', 'bin');
        $destinationBinDirectoryPath = realpath($destinationBinDirectoryPath);

        self::custom_copy($sourceBinDirectoryPath, $destinationBinDirectoryPath);
    }

    private static function custom_copy(string $src, string $dst)
    {
        // open the source directory
        $dir = opendir($src);

        // Make the destination directory if not exist
        @mkdir($dst);

        // Loop through the files in source directory
        while ($file = readdir($dir)) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    // Recursively calling custom copy function
                    // for sub directory
                    custom_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }
}
