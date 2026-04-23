<?php

namespace Hasnayeen\Themes;

use Composer\InstalledVersions;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Hasnayeen\Themes\Commands\ThemesMakeCommand;
use Hasnayeen\Themes\Commands\UpgradeCommand;
use Hasnayeen\Themes\Support\FilamentVersionHelper;
use Illuminate\Foundation\Console\AboutCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ThemesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'themes';

    public static string $viewNamespace = 'themes';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasAssets()
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('hasnayeen/themes');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Themes::class, function () {
            return new Themes;
        });
    }

    public function packageBooted(): void
    {
        // Registrar los assets del paquete en todos los contextos
        // (no solo en consola) para que el UpgradeCommand pueda copiarlos
        // y para que estén disponibles como fallback.
        // El middleware SetTheme registra dinámicamente solo el tema activo en runtime.
        FilamentAsset::register($this->getAssets(), $this->getAssetPackageName());

        if (class_exists(AboutCommand::class) && class_exists(InstalledVersions::class)) {
            AboutCommand::add('Themes', [
                'Version' => InstalledVersions::getPrettyVersion('hasnayeen/themes'),
                'Filament' => 'v' . FilamentVersionHelper::getMajorVersion() . '.x',
                'Themes' => app(Themes::class)
                    ->getThemes()
                    ->map(fn ($item, $key) => $key)
                    ->join(', '),
            ]);
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'hasnayeen/themes';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return app(Themes::class)
            ->getThemes()
            ->map(function (string $theme): Css {
                // En v4+ preferimos la ruta pública si está disponible
                if (FilamentVersionHelper::isV4OrAbove() && method_exists($theme, 'getPublicPath')) {
                    return Css::make($theme::getName(), $theme::getPublicPath());
                }

                return Css::make($theme::getName(), $theme::getPath());
            })
            ->toArray();
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'add_themes_settings_to_users_table',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getCommands(): array
    {
        return [
            UpgradeCommand::class,
            ThemesMakeCommand::class,
        ];
    }
}
