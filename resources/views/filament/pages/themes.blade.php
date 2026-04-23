<x-filament-panels::page>
    <section>
        <header class="flex items-center gap-x-3 overflow-hidden py-4">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('themes::themes.primary_color') }}
                </h3>
                <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                    {{ __('themes::themes.select_base_color') }}
                </p>
            </div>
        </header>

        <div class="flex flex-wrap items-center gap-4 border-t py-6">
            @if ($this->getCurrentTheme() instanceof \Hasnayeen\Themes\Contracts\HasChangeableColor)
                @foreach ($this->getColors() as $name => $color)
                    <button
                        wire:click="setColor('{{ $name }}')"
                        @class([
                            'w-5 h-5 rounded-full transition-transform hover:scale-110',
                            'ring-2 ring-offset-2 ring-primary-500 scale-110' => $this->getColor() === $name,
                        ])
                        title="{{ $name }}"
                        style="background-color: rgb({{ $color[500] }});">
                    </button>
                @endforeach

                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                    <input
                        type="color"
                        id="custom"
                        name="custom"
                        class="w-7 h-7 rounded cursor-pointer border border-gray-300 dark:border-gray-600"
                        wire:change="setColor($event.target.value)"
                        value=""
                    />
                    <label for="custom" class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('themes::themes.custom') }}
                    </label>
                </div>
            @else
                <p class="text-gray-700 dark:text-gray-400">
                    {{ __('themes::themes.no_changing_primary_color') }}
                </p>
            @endif
        </div>
    </section>

    <section>
        <header class="flex items-center gap-x-3 overflow-hidden py-4">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('themes::themes.themes') }}
                </h3>
                <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                    {{ __('themes::themes.select_interface') }}
                </p>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-6 border-t py-6">
            @foreach ($this->getThemes() as $name => $theme)
                @php
                    $noLightMode      = in_array(\Hasnayeen\Themes\Contracts\HasOnlyDarkMode::class, class_implements($theme));
                    $noDarkMode       = in_array(\Hasnayeen\Themes\Contracts\HasOnlyLightMode::class, class_implements($theme));
                    $supportColorChange = in_array(\Hasnayeen\Themes\Contracts\HasChangeableColor::class, class_implements($theme));
                    $isActive         = $this->getCurrentTheme()->getName() === $name;
                @endphp

                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center flex-wrap gap-2">
                            <span>{{ \Illuminate\Support\Str::title($name) }}</span>

                            @if ($supportColorChange)
                                <span
                                    x-data="{}"
                                    x-tooltip="{
                                        content: '{{ __('themes::themes.support_changing_primary_color') }}',
                                        theme: $store.theme,
                                    }"
                                    class="bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center p-1 rounded-full">
                                    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 19.9V16h3a2 2 0 0 0 2-2v-2H5v2c0 1.1.9 2 2 2h3v3.9a2 2 0 1 0 4 0Z" />
                                        <path d="M6 12V2h12v10" />
                                        <path d="M14 2v4" />
                                        <path d="M10 2v2" />
                                    </svg>
                                </span>
                            @endif

                            @if (! $noLightMode)
                                <span
                                    x-data="{}"
                                    x-tooltip="{
                                        content: '{{ __('themes::themes.support_light_mode') }}',
                                        theme: $store.theme,
                                    }"
                                    class="bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center p-1 rounded-full">
                                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="4" />
                                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
                                    </svg>
                                </span>
                            @endif

                            @if (! $noDarkMode)
                                <span
                                    x-data="{}"
                                    x-tooltip="{
                                        content: '{{ __('themes::themes.support_dark_mode') }}',
                                        theme: $store.theme,
                                    }"
                                    class="bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center p-1 rounded-full">
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                                    </svg>
                                </span>
                            @endif

                            @if ($isActive)
                                <span
                                    x-data="{}"
                                    x-tooltip="{
                                        content: '{{ __('themes::themes.theme_active') }}',
                                        theme: $store.theme,
                                    }"
                                    class="bg-success-100 dark:bg-success-900/30 flex items-center justify-center p-1 rounded-full">
                                    <svg class="w-4 h-4 text-success-600 dark:text-success-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" />
                                        <path d="m9 12 2 2 4-4" />
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </x-slot>

                    <x-slot name="headerEnd">
                        <x-filament::button
                            wire:click="setTheme('{{ $name }}')"
                            size="xs"
                            :outlined="! $isActive"
                            :color="$isActive ? 'success' : 'primary'"
                        >
                            {{ $isActive ? __('themes::themes.active') : __('themes::themes.select') }}
                        </x-filament::button>
                    </x-slot>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            @if ($noLightMode)
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 pb-4">
                                    {{ __('themes::themes.no_light_mode') }}
                                </h3>
                            @else
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 pb-4">
                                    {{ __('themes::themes.light') }}
                                </h3>
                                <img
                                    src="{{ url('https://raw.githubusercontent.com/Hasnayeen/themes/3.x/assets/' . $name . '-light.png') }}"
                                    alt="{{ $name }} theme preview (light version)"
                                    class="border dark:border-gray-700 rounded-lg w-full object-cover"
                                    loading="lazy"
                                >
                            @endif
                        </div>

                        <div>
                            @if ($noDarkMode)
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 pb-4">
                                    {{ __('themes::themes.no_dark_mode') }}
                                </h3>
                            @else
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 pb-4">
                                    {{ __('themes::themes.dark') }}
                                </h3>
                                <img
                                    src="{{ url('https://raw.githubusercontent.com/Hasnayeen/themes/3.x/assets/' . $name . '-dark.png') }}"
                                    alt="{{ $name }} theme preview (dark version)"
                                    class="border dark:border-gray-700 rounded-lg w-full object-cover"
                                    loading="lazy"
                                >
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    </section>
</x-filament-panels::page>
