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
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class Themes
{
    protected $collection;

    public function __construct()
    {
        $this->collection = collect([
            DefaultTheme::getName() => DefaultTheme::class,
            Dracula::getName() => Dracula::class,
            // Nord::getName() => Nord::class,
            Sunset::getName() => Sunset::class,
        ]);
    }

    public function getThemes(): Collection
    {
        return $this->collection;
    }

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
        $name = $this->collection->first(fn ($item) => $item::getName() === $theme);
        if ($name) {
            return new $name;
        }

        return app($this->collection->first());
    }

    public function getCurrentTheme(): Theme
    {
        if (config('themes.mode') === 'global') {
            return $this->make(cache('theme') ?? config('themes.default.theme', 'default'));
        }

        [$theme, $_color] = $this->getUserTheme();

        return $this->make($theme);
    }

    public function getCurrentThemeColor(): array
    {
        if (! $this->getCurrentTheme() instanceof HasChangeableColor) {
            return $this->getCurrentTheme()->getThemeColor();
        }

        if (config('themes.mode') === 'global') {
            $color = cache('theme_color') ?? config('themes.default.theme_color');
        } else {
            [$_theme, $color] = $this->getUserTheme();
        }

        return Arr::has($this->getCurrentTheme()->getThemeColor(), $color)
            ? ['primary' => Arr::get($this->getCurrentTheme()->getThemeColor(), $color)]
            : ($color ? ['primary' => $color] : $this->getCurrentTheme()->getPrimaryColor());
    }

    protected function getUserTheme(): array
    {
        $user = Filament::getCurrentPanel()->auth()->user();
        $tenant = Filament::getTenant();
        $id = tenant()?->id;

        $cacheKey = "user_theme_{$id}_{$tenant->id}_{$user->id}";

        // Attempt to retrieve from cache
        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user, $tenant) {
            $userWithPivot = $tenant->members()->withPivot(['theme', 'theme_color'])->firstWhere('user_id', $user->id);

            return [
                $userWithPivot->pivot->theme ?? config('themes.default.theme', 'default'),
                $userWithPivot->pivot->theme_color ?? config('themes.default.theme_color'),
            ];
        });
    }
}
