<?php

namespace Hasnayeen\Themes\Themes;

use Filament\Panel;
use Filament\Support\Colors\Color;
use Hasnayeen\Themes\Contracts\CanModifyPanelConfig;
use Hasnayeen\Themes\Contracts\Theme;
use Hasnayeen\Themes\Support\FilamentVersionHelper;

class Nord implements CanModifyPanelConfig, Theme
{
    public static function getName(): string
    {
        return 'nord';
    }

    public static function getPath(): string
    {
        return __DIR__ . '/../../resources/dist/nord.css';
    }

    public static function getPublicPath(): string
    {
        return 'vendor/hasnayeen/themes/nord.css';
    }

    public function getThemeColor(): array
    {
        return [
            'primary' => Color::hex('#8FBCBB'),
            'secondary' => Color::hex('#2E3440'),
            'info' => Color::hex('#5E81AC'),
            'success' => Color::hex('#A3BE8C'),
            'warning' => Color::hex('#D08770'),
            'danger' => Color::hex('#BF616A'),
        ];
    }

    public function modifyPanelConfig(Panel $panel): Panel
    {
        $panel = $panel
            ->topNavigation()
            ->sidebarCollapsibleOnDesktop(false);

        // En v4+ el hook 'panels::page.start' puede haberse renombrado o
        // el tenant-menu gestionarse de otra forma. Usamos renderHook con
        // compatibilidad defensiva.
        if (FilamentVersionHelper::isV4OrAbove()) {
            // En v4/v5 el componente x-filament-panels::tenant-menu sigue existiendo,
            // pero el hook puede diferir. Intentamos el hook estándar.
            try {
                $panel->renderHook(
                    'panels::topbar.start',
                    fn () => view('themes::filament.hooks.tenant-menu')
                );
            } catch (\Throwable) {
                // Si el hook no existe en esta versión, lo omitimos silenciosamente.
            }
        } else {
            // v3: hook original
            $panel->renderHook(
                'panels::page.start',
                fn () => view('themes::filament.hooks.tenant-menu')
            );
        }

        return $panel;
    }
}
