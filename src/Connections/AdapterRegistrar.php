<?php
declare(strict_types=1);


namespace Attlaz\Project\Connections;


class AdapterRegistrar
{
    private static $adapters = [];

    public static function register(string $adapterName, string $path): void
    {
        die('register ok');
        if (isset(self::$adapters[$adapterName])) {
            throw new \LogicException(
                $adapterName . '\' from \'' . $path . '\' '
                . 'has been already defined in \'' . self::$adapters[$adapterName] . '\'.'
            );
        } else {
            self::$adapters[$adapterName] = str_replace('\\', '/', $path);
        }
    }

    public static function getPath(string $adapterName): ?string
    {
        if (isset(self::$adapters[$adapterName])) {
            return self::$adapters[$adapterName];
        }
        return null;
    }

}
