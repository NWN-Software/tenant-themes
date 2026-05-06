<?php

namespace Hasnayeen\Themes;

use Filament\Facades\Filament;
use Hasnayeen\Themes\Contracts\HasChangeableColor;
use Hasnayeen\Themes\Contracts\Theme;
use Hasnayeen\Themes\Themes\DefaultTheme;
use Hasnayeen\Themes\Themes\Dracula;
use Hasnayeen\Themes\Themes\Nord;
use Hasnayeen\Themes\Themes\Sunset;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Themes
{
    /** @var Collection<string, class-string<Theme>> */
    protected Collection $collection;

    public function __construct()
    {
        $this->collection = collect([
            DefaultTheme::getName() => DefaultTheme::class,
            Dracula::getName() => Dracula::class,
            Nord::getName() => Nord::class,
            Sunset::getName() => Sunset::class,
        ]);
    }

    /**
     * @return Collection<string, class-string<Theme>>
     */
    public function getThemes(): Collection
    {
        return $this->collection;
    }

    /**
     * @param  array<string, class-string<Theme>>  $themes
     */
    public function register(array $themes, bool $override = false): self
    {
        if (empty($themes)) {
            throw new InvalidArgumentException('No themes provided.');
        }

        if ($override) {
            $this->collection = collect($themes);

            return $this;
        }

        $this->collection = $this->collection->merge($themes);

        return $this;
    }

    public function make(string $theme): Theme
    {
        /** @var class-string<Theme>|null $themeClass */
        $themeClass = $this->collection->first(fn ($item) => $item::getName() === $theme);

        if ($themeClass) {
            return new $themeClass;
        }

        /** @var class-string<Theme> $first */
        $first = $this->collection->first();

        return app($first);
    }

    public function getCurrentTheme(): Theme
    {
        if (config('themes.mode') === 'global') {
            return $this->make(cache('theme') ?? config('themes.default.theme', 'default'));
        }

        $user = Filament::getCurrentPanel()->auth()->user();

        return $this->make($user->theme ?? config('themes.default.theme', 'default'));
    }

    public function getCurrentThemeColor(): array
    {
        $theme = $this->getCurrentTheme();

        if (! $theme instanceof HasChangeableColor) {
            return $theme->getThemeColor();
        }

        if (config('themes.mode') === 'global') {
            $color = cache('theme_color') ?? config('themes.default.theme_color');
        } else {
            $user = Filament::getCurrentPanel()->auth()->user();
            $color = $user->theme_color ?? config('themes.default.theme_color');
        }

        return Arr::has($theme->getThemeColor(), $color)
            ? ['primary' => Arr::get($theme->getThemeColor(), $color)]
            : ($color ? ['primary' => $color] : $theme->getPrimaryColor());
    }
}
