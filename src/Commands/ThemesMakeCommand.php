<?php

namespace Hasnayeen\Themes\Commands;

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Hasnayeen\Themes\Support\FilamentVersionHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class ThemesMakeCommand extends Command
{
    use CanManipulateFiles;

    protected $description = 'Create a new theme class';

    protected $signature = 'themes:make {name?} {--panel=} {--F|force}';

    public function handle(): int
    {
        $theme = (string) str(
            $this->argument('name') ??
            text(
                label: 'What is the theme name?',
                placeholder: 'AwesomeTheme',
                required: true,
            ),
        )
            ->trim('/')
            ->trim('\\')
            ->trim(' ')
            ->replace('/', '\\');

        $themeClass = (string) str($theme)->afterLast('\\');
        $themeNamespace = str($theme)->contains('\\') ?
            (string) str($theme)->beforeLast('\\') :
            '';
        $name = Str::kebab($themeClass);

        $panel = $this->option('panel');

        if ($panel) {
            $panel = Filament::getPanel($panel);
        }

        if (! $panel) {
            $panels = Filament::getPanels();

            /** @var Panel $panel */
            $panel = (count($panels) > 1) ? $panels[select(
                label: 'Which panel would you like to create this in?',
                options: array_map(
                    fn (Panel $panel): string => $panel->getId(),
                    $panels,
                ),
                default: Filament::getDefaultPanel()->getId()
            )] : Arr::first($panels);
        }

        $panelId = $panel->getId();

        $result = $this->createCssFile($theme, $panel, $panelId, $name);

        if ($result !== static::SUCCESS) {
            return $result;
        }

        // En v4+ la estructura de directorios de paneles cambió:
        // v3: app/Filament/Themes/  (si panel path es vacío)
        // v4: app/Filament/{PanelId}/Themes/
        $path = $this->resolvePath($panel, $panelId);
        $namespace = $this->resolveNamespace($panel, $panelId);

        $fullPath = (string) str($theme)
            ->prepend('/')
            ->prepend($path)
            ->replace('\\', '/')
            ->replace('//', '/')
            ->append('.php');

        if (! $this->option('force') && $this->checkForCollision([$fullPath])) {
            return static::INVALID;
        }

        $this->copyStubToApp('Theme', $fullPath, [
            'class' => $themeClass,
            'name' => $name,
            'panel' => $panelId,
            'namespace' => str($namespace) . ($themeNamespace !== '' ? "\\{$themeNamespace}" : ''),
            'method' => file_exists(base_path('vite.config.js')) ? 'viteTheme' : 'theme',
        ]);

        $this->components->info("<fg=green>Successfully created {$themeNamespace}/{$theme}.php!");

        $this->components->warn('Action is required to complete the theme setup:');
        $this->components->bulletList([
            "Add a new item to the `input` array of `vite.config.js`: `<fg=magenta>resources/css/filament/{$panelId}/themes/{$name}.css</>`.",
            "Make sure to register the theme in `<fg=magenta>ThemesPlugin::registerTheme([{$themeClass}::getName() => {$themeClass}::class])</>`",
            'Finally, run `npm run build` to compile the theme.',
        ]);

        return static::SUCCESS;
    }

    /**
     * Resuelve la ruta base para el archivo de la clase del tema.
     * Compatible con la estructura de directorios de v3 y v4+.
     */
    protected function resolvePath(Panel $panel, string $panelId): string
    {
        if (FilamentVersionHelper::isV4OrAbove()) {
            // En v4+ los paneles tienen su propio subdirectorio por id
            $panelPath = $panel->getPath();

            if ($panelPath) {
                return app_path('Filament/' . Str::title($panelPath) . '/Themes');
            }

            // Intentar con el ID del panel como directorio
            return app_path('Filament/' . Str::studly($panelId) . '/Themes');
        }

        // v3: comportamiento original
        $panelPath = $panel->getPath();

        return app_path('Filament/' . ($panelPath ? Str::title($panelPath) . '/' : '') . 'Themes');
    }

    /**
     * Resuelve el namespace para la clase del tema.
     */
    protected function resolveNamespace(Panel $panel, string $panelId): string
    {
        if (FilamentVersionHelper::isV4OrAbove()) {
            $panelPath = $panel->getPath();

            if ($panelPath) {
                return 'App\\Filament\\' . Str::title($panelPath) . '\\Themes';
            }

            return 'App\\Filament\\' . Str::studly($panelId) . '\\Themes';
        }

        // v3: comportamiento original
        $panelPath = $panel->getPath();

        return 'App\\Filament\\' . ($panelPath ? Str::title($panelPath) . '\\' : '') . 'Themes';
    }

    private function createCssFile(string $theme, Panel $panel, string $panelId, string $name): int
    {
        exec('npm -v', $npmVersion, $npmVersionExistCode);

        if ($npmVersionExistCode !== 0) {
            $this->error('Node.js is not installed. Please install before continuing.');

            return static::FAILURE;
        }

        $this->info("Using NPM v{$npmVersion[0]}");

        if (FilamentVersionHelper::isV4OrAbove()) {
            // v4+: Tailwind CSS v4, no necesita las mismas dependencias que v3
            exec('npm install tailwindcss postcss autoprefixer --save-dev');
        } else {
            // v3: Tailwind CSS v3
            exec('npm install tailwindcss @tailwindcss/forms @tailwindcss/typography postcss autoprefixer --save-dev');
        }

        $cssFilePath = resource_path("css/filament/{$panelId}/themes/{$name}.css");
        $tailwindConfigFilePath = resource_path("css/filament/{$panelId}/themes/tailwind.{$name}.config.js");

        if (! $this->option('force') && $this->checkForCollision([
            $cssFilePath,
            $tailwindConfigFilePath,
        ])) {
            return static::INVALID;
        }

        $classPathPrefix = $this->resolveClassPathPrefix($panel);

        $viewPathPrefix = str($classPathPrefix)
            ->explode('/')
            ->map(fn ($segment) => Str::lower(Str::kebab($segment)))
            ->implode('/');

        $this->copyStubToApp('ThemeCss', $cssFilePath, [
            'panel' => $panelId,
            'theme' => $name,
        ]);

        $this->copyStubToApp('ThemeTailwindConfig', $tailwindConfigFilePath, [
            'classPathPrefix' => $classPathPrefix,
            'viewPathPrefix' => $viewPathPrefix,
        ]);

        $this->components->info("<fg=green>Successfully created resources/css/filament/{$panelId}/themes/{$theme}.css and resources/css/filament/{$panelId}/themes/tailwind.{$theme}.config.js!</>");

        if (! file_exists(base_path('vite.config.js'))) {
            $this->components->warn('Action is required to complete the theme setup:');
            $this->components->bulletList([
                "It looks like you don't have Vite installed. Please use your asset bundling system of choice to compile `resources/css/filament/{$panelId}/themes/{$name}.css` into `public/css/filament/{$panelId}/themes/{$name}.css`.",
                'If you\'re not currently using a bundler, we recommend using Vite. Alternatively, you can use the Tailwind CLI with the following command:',
                "npx tailwindcss --input ./resources/css/filament/{$panelId}/themes/{$name}.css --output ./public/css/filament/{$panelId}/themes/{$name}.css --config ./resources/css/filament/{$panelId}/themes/tailwind.{$name}.config.js --minify",
                "Make sure to register the theme in the {$name} class inside `modifyPanelConfig` using `->theme(asset('css/filament/{$panelId}/themes/{$name}.css'))`",
            ]);

            return static::SUCCESS;
        }

        $postcssConfigPath = base_path('postcss.config.js');

        if (! file_exists($postcssConfigPath)) {
            $this->copyStubToApp('ThemePostcssConfig', $postcssConfigPath);
            $this->components->info('Successfully created postcss.config.js!');
        }

        return static::SUCCESS;
    }

    /**
     * Resuelve el prefijo del path de clases para el stub de TailwindConfig.
     * Compatible con la estructura de v3 y v4+.
     */
    protected function resolveClassPathPrefix(Panel $panel): string
    {
        $pageDirectories = $panel->getPageDirectories();

        if (empty($pageDirectories)) {
            return '';
        }

        $firstDir = Arr::first($pageDirectories);

        // En v4+ la estructura es diferente
        if (FilamentVersionHelper::isV4OrAbove()) {
            return (string) str($firstDir)
                ->afterLast('Filament/')
                ->beforeLast('Pages');
        }

        // v3: comportamiento original
        return (string) str($firstDir)
            ->afterLast('Filament/')
            ->beforeLast('Pages');
    }
}
