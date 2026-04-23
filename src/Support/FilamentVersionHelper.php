<?php

namespace Hasnayeen\Themes\Support;

use Composer\InstalledVersions;

class FilamentVersionHelper
{
    private static ?int $majorVersion = null;

    /**
     * Retorna el major version de filament/filament instalado (3, 4, 5, ...).
     */
    public static function getMajorVersion(): int
    {
        if (static::$majorVersion !== null) {
            return static::$majorVersion;
        }

        try {
            $version = InstalledVersions::getPrettyVersion('filament/filament') ?? '3.0.0';
            static::$majorVersion = (int) explode('.', ltrim($version, 'v'))[0];
        } catch (\Throwable) {
            static::$majorVersion = 3;
        }

        return static::$majorVersion;
    }

    public static function isV3(): bool
    {
        return static::getMajorVersion() === 3;
    }

    public static function isV4OrAbove(): bool
    {
        return static::getMajorVersion() >= 4;
    }

    public static function isV5OrAbove(): bool
    {
        return static::getMajorVersion() >= 5;
    }
}
