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

        return $user?->theme_color;
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
            $user->theme_color = $color;
            $user->save();
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
            $user = Filament::auth()->user();
            $user->theme = $theme;
            $user->save();
        }

        Notification::make()
            ->title(__('themes::themes.theme_set_to') . ' ' . $theme . '.')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
