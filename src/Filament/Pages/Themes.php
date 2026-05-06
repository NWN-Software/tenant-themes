<?php

namespace Hasnayeen\Themes\Filament\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Hasnayeen\Themes\Contracts\Theme;
use Hasnayeen\Themes\ThemesPlugin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Themes extends Page
{
    // v4/v5: tipo actualizado de ?string a string|BackedEnum|null
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $title = 'Appearance';

    // v4/v5: $view es instancia (no estático) en la clase base Page
    protected string $view = 'themes::filament.pages.themes';

    public function mount(): void
    {
        abort_unless(ThemesPlugin::canView(), 403);
    }

    public function getTitle(): string | Htmlable
    {
        return __('themes::themes.appearance');
    }

    public function getThemes(): Collection
    {
        return app(\Hasnayeen\Themes\Themes::class)->getThemes();
    }

    public function getCurrentTheme(): Theme
    {
        return app(\Hasnayeen\Themes\Themes::class)->getCurrentTheme();
    }

    public function getColor(): ?string
    {
        if (config('themes.mode') === 'global') {
            return cache('theme_color');
        }

        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        return $tenant->members()->withPivot('theme_color')->find($user->id)->pivot->theme_color;
    }

    public function getColors(): array
    {
        return Arr::except(Color::all(), ['gray', 'zinc', 'neutral', 'stone']);
    }

    public function setColor(string $color): void
    {
        if (config('themes.mode') === 'global') {
            cache(['theme_color' => $color]);
        } else {
            $user = Filament::auth()->user();
            $tenant = Filament::getTenant();
            $tenant->members()->updateExistingPivot($user->id, ['theme_color' => $color]);
            $this->setChangeToCache($user, $tenant);
        }

        Notification::make()
            ->title(__('themes::themes.primary_color_set') . ' ' . $color . '.')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    public function setTheme(string $theme): void
    {
        if (config('themes.mode') === 'global') {
            cache(['theme' => $theme]);
        } else {
            $user = Filament::getCurrentPanel()->auth()->user();
            $tenant = Filament::getTenant();
            $tenant->members()->updateExistingPivot($user->id, ['theme' => $theme]);
            $this->setChangeToCache($user, $tenant);
        }

        Notification::make()
            ->title(__('themes::themes.theme_set_to') . ' ' . $theme . '.')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    public function setChangeToCache($user, $tenant)
    {
        $id = tenant()?->id;
        $cacheKey = "user_theme_{$id}_{$tenant->id}_{$user->id}";

        Cache::forget($cacheKey);

        Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user, $tenant) {
            $userWithPivot = $tenant->members()->withPivot(['theme', 'theme_color'])->firstWhere('user_id', $user->id);

            return [
                $userWithPivot->pivot->theme ?? config('themes.default.theme', 'default'),
                $userWithPivot->pivot->theme_color ?? config('themes.default.theme_color'),
            ];
        });
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
