<x-filament-panels::page>

    {{-- COLOR PRIMARIO --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-col gap-y-1 px-6 py-4">
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                {{ __('themes::themes.primary_color') }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('themes::themes.select_base_color') }}
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-white/5 px-6 py-4">
            @if ($this->getCurrentTheme() instanceof \Hasnayeen\Themes\Contracts\HasChangeableColor)
                <div class="flex flex-wrap items-center gap-4">
                    @foreach ($this->getColors() as $name => $color)
                        <button
                            wire:click="setColor('{{ $name }}')"
                            title="{{ $name }}"
                            style="background-color: rgb({{ $color[500] }});"
                            class="w-6 h-6 rounded-full transition-all hover:scale-110 focus:outline-none {{ $this->getColor() === $name ? 'ring-2 ring-offset-2 ring-gray-800 scale-110' : '' }}"
                        ></button>
                    @endforeach
                    <div class="flex items-center gap-2">
                        <input type="color" id="custom_color"
                            class="w-7 h-7 rounded cursor-pointer border border-gray-300 dark:border-gray-600"
                            wire:change="setColor($event.target.value)" />
                        <label for="custom_color" class="text-sm text-gray-700 dark:text-gray-300">
                            {{ __('themes::themes.custom') }}
                        </label>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('themes::themes.no_changing_primary_color') }}
                </p>
            @endif
        </div>
    </div>

    {{-- SELECCIÓN DE TEMA --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-col gap-y-1 px-6 py-4">
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                {{ __('themes::themes.themes') }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('themes::themes.select_interface') }}
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-white/5 px-6 py-4">
            <div class="grid grid-cols-1 gap-6">
                @foreach ($this->getThemes() as $name => $themeClass)
                    @php
                        $isDarkOnly  = in_array(\Hasnayeen\Themes\Contracts\HasOnlyDarkMode::class, class_implements($themeClass));
                        $isLightOnly = in_array(\Hasnayeen\Themes\Contracts\HasOnlyLightMode::class, class_implements($themeClass));
                        $isColorable = in_array(\Hasnayeen\Themes\Contracts\HasChangeableColor::class, class_implements($themeClass));
                        $isActive    = $this->getCurrentTheme()::getName() === $name;
                    @endphp

                    <div class="rounded-xl border {{ $isActive ? 'border-primary-500 ring-2 ring-primary-500/20' : 'border-gray-200 dark:border-white/10' }} bg-white dark:bg-gray-800 overflow-hidden">

                        {{-- Cabecera tarjeta --}}
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/10">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white capitalize">
                                    {{ $name }}
                                </span>

                                @if ($isColorable)
                                    <span class="rounded-full bg-primary-50 dark:bg-primary-900/30 px-2 py-0.5 text-xs font-medium text-primary-700 dark:text-primary-300">
                                        🎨 Color
                                    </span>
                                @endif
                                @if (!$isDarkOnly)
                                    <span class="rounded-full bg-yellow-50 dark:bg-yellow-900/30 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:text-yellow-300">
                                        ☀️ Light
                                    </span>
                                @endif
                                @if (!$isLightOnly)
                                    <span class="rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-300">
                                        🌙 Dark
                                    </span>
                                @endif
                                @if ($isActive)
                                    <span class="rounded-full bg-green-50 dark:bg-green-900/30 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">
                                        ✓ {{ __('themes::themes.active') }}
                                    </span>
                                @endif
                            </div>

                            {{-- Botón seleccionar --}}
                            @if (!$isActive)
                                <button
                                    wire:click="setTheme('{{ $name }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="setTheme('{{ $name }}')"
                                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold bg-primary-600 text-white hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1 transition-colors cursor-pointer"
                                >
                                    <span wire:loading.remove wire:target="setTheme('{{ $name }}')">
                                        {{ __('themes::themes.select') }}
                                    </span>
                                    <span wire:loading wire:target="setTheme('{{ $name }}')">
                                        ...
                                    </span>
                                </button>
                            @else
                                <span class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                    {{ __('themes::themes.active') }}
                                </span>
                            @endif
                        </div>

                        {{-- Previews --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5">
                            <div>
                                @if ($isDarkOnly)
                                    <div class="flex items-center justify-center h-28 rounded-lg bg-gray-100 dark:bg-gray-700 border border-dashed border-gray-300 dark:border-gray-600">
                                        <p class="text-xs text-gray-400">{{ __('themes::themes.no_light_mode') }}</p>
                                    </div>
                                @else
                                    <p class="text-xs font-medium text-gray-400 mb-2 uppercase tracking-wide">{{ __('themes::themes.light') }}</p>
                                    <img
                                        src="https://raw.githubusercontent.com/Hasnayeen/themes/3.x/assets/{{ $name }}-light.png"
                                        alt="{{ $name }} light"
                                        class="rounded-lg border border-gray-200 dark:border-gray-700 w-full object-cover"
                                        loading="lazy"
                                        onerror="this.parentNode.innerHTML='<div class=\'flex items-center justify-center h-28 rounded-lg bg-gray-100 border border-dashed border-gray-200\'><p class=\'text-xs text-gray-400\'>Sin preview</p></div>'"
                                    >
                                @endif
                            </div>

                            <div>
                                @if ($isLightOnly)
                                    <div class="flex items-center justify-center h-28 rounded-lg bg-gray-100 dark:bg-gray-700 border border-dashed border-gray-300 dark:border-gray-600">
                                        <p class="text-xs text-gray-400">{{ __('themes::themes.no_dark_mode') }}</p>
                                    </div>
                                @else
                                    <p class="text-xs font-medium text-gray-400 mb-2 uppercase tracking-wide">{{ __('themes::themes.dark') }}</p>
                                    <img
                                        src="https://raw.githubusercontent.com/Hasnayeen/themes/3.x/assets/{{ $name }}-dark.png"
                                        alt="{{ $name }} dark"
                                        class="rounded-lg border border-gray-200 dark:border-gray-700 w-full object-cover"
                                        loading="lazy"
                                        onerror="this.parentNode.innerHTML='<div class=\'flex items-center justify-center h-28 rounded-lg bg-gray-100 border border-dashed border-gray-200\'><p class=\'text-xs text-gray-400\'>Sin preview</p></div>'"
                                    >
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <p class="text-xs text-gray-400 dark:text-gray-500 text-center pb-2">
        hasnayeen/themes — Filament v4/v5 compatible fork
    </p>

</x-filament-panels::page>
