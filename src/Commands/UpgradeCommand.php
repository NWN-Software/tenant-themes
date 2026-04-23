<?php

namespace Hasnayeen\Themes\Commands;

use Hasnayeen\Themes\Support\FilamentVersionHelper;
use Hasnayeen\Themes\Themes;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class UpgradeCommand extends Command
{
    protected $description = 'Upgrade themes to the latest version (copies CSS assets to public/)';

    protected $signature = 'themes:upgrade';

    public function handle(): int
    {
        if (FilamentVersionHelper::isV4OrAbove()) {
            // En Filament v4/v5 el comando nativo es `filament:assets`
            $this->components->info('Running filament:assets to publish theme CSS files...');
            $this->call('filament:assets');
        } else {
            // Filament v3: copia manual
            $this->copyAssetsManually();
        }

        $this->components->info('Themes package upgraded successfully!');

        return static::SUCCESS;
    }

    protected function copyAssetsManually(): void
    {
        /** @var Themes $themes */
        $themes = app(Themes::class);

        foreach ($themes->getThemes() as $themeClass) {
            $from = $themeClass::getPath();
            $to = public_path('vendor/hasnayeen/themes/' . $themeClass::getName() . '.css');

            $this->copyAsset($from, $to);
            $this->components->twoColumnDetail($themeClass::getName(), '<fg=green>✓</>');
        }
    }

    protected function copyAsset(string $from, string $to): void
    {
        $filesystem = app(Filesystem::class);

        [$from, $to] = str_replace('/', DIRECTORY_SEPARATOR, [$from, $to]);

        $filesystem->ensureDirectoryExists(
            (string) str($to)->beforeLast(DIRECTORY_SEPARATOR),
        );

        if (! file_exists($from)) {
            $this->components->warn("Source file not found: {$from}");

            return;
        }

        $filesystem->copy($from, $to);
    }
}
