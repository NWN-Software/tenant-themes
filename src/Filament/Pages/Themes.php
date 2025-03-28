<?php

namespace Hasnayeen\Themes\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Hasnayeen\Themes\ThemesPlugin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Themes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $title = 'Appearance';

    public function getTitle(): string|Htmlable
    {
        return __('themes::themes.appearance');
    }

    protected static string $view = 'themes::filament.pages.themes';

    public function mount(): void
    {
        abort_unless(ThemesPlugin::canView(), 403);
    }

    public function getThemes()
    {
        return app(\Hasnayeen\Themes\Themes::class)->getThemes();
    }

    public function getCurrentTheme()
    {
        return app(\Hasnayeen\Themes\Themes::class)->getCurrentTheme();
    }

    public function getColor()
    {
        if (config('themes.mode') === 'global') {
            return cache('theme_color');
        }

        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        return $tenant->members()->withPivot('theme_color')->find($user->id)->pivot->theme_color;
    }

    public function getColors()
    {
        return Arr::except(Color::all(), ['gray', 'zinc', 'neutral', 'stone']);
    }

    public function setColor(string $color)
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
            ->title(__('themes::themes.primary_color_set').' '.$color.'.')
            ->success()
            ->send();

        return $this->redirect(self::getUrl());
    }

    public function setTheme(string $theme)
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
            ->title(__('themes::themes.theme_set_to').' '.$theme.'.')
            ->success()
            ->send();

        return $this->redirect(self::getUrl());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getFooter(): ?View
    {
        return view('themes::filament.pages.themes-footer');
    }

    public function setChangeToCache($user, $tenant)
    {
        $id = tenant()?->id;
        $cacheKey = "user_theme_{$id}_{$tenant->id}_{$user->id}";

        Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user, $tenant) {
            $userWithPivot = $tenant->members()->withPivot(['theme', 'theme_color'])->firstWhere('user_id', $user->id);
            return [
                $userWithPivot->pivot->theme ?? config('themes.default.theme', 'default'),
                $userWithPivot->pivot->theme_color ?? config('themes.default.theme_color'),
            ];
        });
    }
}
