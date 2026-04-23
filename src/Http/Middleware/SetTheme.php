<?php

namespace Hasnayeen\Themes\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Hasnayeen\Themes\Contracts\CanModifyPanelConfig;
use Hasnayeen\Themes\Contracts\HasOnlyDarkMode;
use Hasnayeen\Themes\Contracts\HasOnlyLightMode;
use Hasnayeen\Themes\Filament\Pages\Themes as ThemesPage;
use Hasnayeen\Themes\Support\FilamentVersionHelper;
use Hasnayeen\Themes\Themes;
use Hasnayeen\Themes\ThemesPlugin;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Themes $themes */
        $themes = app(Themes::class);
        $panel = Filament::getCurrentPanel();

        if (! $panel->hasPlugin('themes')) {
            return $next($request);
        }

        $this->registerUserMenuItem($panel);

        FilamentColor::register($themes->getCurrentThemeColor());

        $currentTheme = $themes->getCurrentTheme();

        $this->registerThemeAsset($currentTheme);

        $this->applyDarkModeSettings($panel, $currentTheme);

        if ($currentTheme instanceof CanModifyPanelConfig) {
            $currentTheme->modifyPanelConfig($panel);
        }

        return $next($request);
    }

    /**
     * Registra el item en el user menu de forma compatible con v3/v4/v5.
     *
     * En v3 el array de getUserMenuItems() es keyed por label string.
     * En v4+ el array puede estar keyed por un identificador interno o vacío
     * hasta el boot, por lo que usamos una detección más robusta.
     */
    protected function registerUserMenuItem(mixed $panel): void
    {
        $label = __('themes::themes.themes');

        /**
         * Protección contra duplicados compatible con todas las versiones:
         * Intentamos buscar si ya existe un item con la URL de la página de temas.
         */
        $existingItems = $panel->getUserMenuItems();
        $themePageUrl = ThemesPage::getUrl();

        $alreadyAdded = collect($existingItems)->contains(function ($item) use ($themePageUrl) {
            if (! $item instanceof MenuItem) {
                return false;
            }

            // En v3/v4 el URL se puede obtener con getUrl() o url()
            $itemUrl = null;
            if (method_exists($item, 'getUrl')) {
                $itemUrl = $item->getUrl();
            } elseif (method_exists($item, 'url')) {
                try {
                    $itemUrl = value($item->url);
                } catch (\Throwable) {
                    // Si el closure falla, continuamos
                }
            }

            return $itemUrl === $themePageUrl;
        });

        if ($alreadyAdded) {
            return;
        }

        if (! ThemesPlugin::canView()) {
            return;
        }

        $panel->userMenuItems([
            $label => MenuItem::make()
                ->label(fn () => __('themes::themes.themes'))
                ->icon(config('themes.icon'))
                ->url(ThemesPage::getUrl()),
        ]);
    }

    /**
     * Registra el CSS del tema activo.
     *
     * En v3 usamos getPath() (ruta absoluta al dist local).
     * En v4/v5 usamos getPublicPath() si está disponible (ruta pública).
     */
    protected function registerThemeAsset(mixed $currentTheme): void
    {
        $name = $currentTheme::getName();

        if (FilamentVersionHelper::isV4OrAbove() && method_exists($currentTheme, 'getPublicPath')) {
            // En v4+ los assets se sirven desde public/ ya publicados
            FilamentAsset::register([
                Css::make($name, $currentTheme::getPublicPath()),
            ], 'hasnayeen/themes');
        } else {
            // v3: path absoluto al CSS local del paquete
            FilamentAsset::register([
                Css::make($name, $currentTheme::getPath()),
            ], 'hasnayeen/themes');
        }
    }

    /**
     * Aplica la configuración de dark mode de forma compatible con v3/v4/v5.
     *
     * En Filament v3:
     *   $panel->darkMode(bool $condition, bool $isForced = false)
     *
     * En Filament v4+:
     *   $panel->darkMode(bool $condition) — el segundo argumento fue eliminado.
     *   Para forzar solo dark: $panel->darkMode(true) + el tema controla el toggle.
     *   Para forzar solo light: $panel->darkMode(false).
     */
    protected function applyDarkModeSettings(mixed $panel, mixed $currentTheme): void
    {
        // Si el panel ya forzó un modo externamente, lo respetamos.
        if (method_exists($panel, 'hasDarkModeForced') && $panel->hasDarkModeForced()) {
            return;
        }

        $supportsLightMode = ! ($currentTheme instanceof HasOnlyDarkMode);
        $supportsDarkMode = ! ($currentTheme instanceof HasOnlyLightMode);

        if (! method_exists($panel, 'darkMode')) {
            return;
        }

        if (FilamentVersionHelper::isV3()) {
            // v3: darkMode(bool $hasDarkMode, bool $isForced)
            // Forzar si el tema solo soporta un modo
            $forceMode = ! $supportsLightMode || ! $supportsDarkMode;
            $panel->darkMode($supportsDarkMode, $forceMode);
        } else {
            // v4/v5: darkMode(bool $condition) — un solo argumento
            // Si el tema es solo oscuro: habilitamos darkMode pero no podemos
            // "forzarlo" vía API; el CSS del tema se encarga visualmente.
            if (! $supportsDarkMode) {
                // Solo light mode: deshabilitamos dark mode
                $panel->darkMode(false);
            } elseif (! $supportsLightMode) {
                // Solo dark mode: habilitamos
                $panel->darkMode(true);
            }
            // Si soporta ambos modos, no tocamos la configuración del panel
        }
    }
}
