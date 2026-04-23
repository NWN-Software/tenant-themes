<?php

namespace Hasnayeen\Themes\Contracts;

interface Theme
{
    /**
     * Nombre único del tema, usado como clave de registro.
     */
    public static function getName(): string;

    /**
     * Ruta absoluta al archivo CSS compilado del tema.
     * Se usa en Filament v3 para registrar el asset con FilamentAsset.
     */
    public static function getPath(): string;

    /**
     * Ruta pública relativa al CSS del tema (relativa a /public).
     * Requerida por Filament v4+ para el registro de assets públicos.
     * Ejemplo: 'vendor/hasnayeen/themes/default.css'
     */
    public static function getPublicPath(): string;

    /**
     * Array de colores del tema.
     * Debe retornar al menos la clave 'primary'.
     */
    public function getThemeColor(): array;
}
