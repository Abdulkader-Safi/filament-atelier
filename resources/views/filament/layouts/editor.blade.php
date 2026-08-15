@props(['livewire' => null])

{{-- Full-screen shell for the builder. Deliberately not Filament's panel
     layout: no sidebar, no topbar, no page heading. The builder owns the
     whole viewport, and the way back is the button in its own toolbar.

     It still has to load the panel's theme, fonts and CSS variables, or
     Filament's own components render unstyled. --}}
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="ltr"
    @class(['fi', 'dark' => filament()->hasDarkMode() && filament()->hasDarkModeForced()])
>
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    @if ($favicon = filament()->getFavicon())
        <link rel="icon" href="{{ $favicon }}">
    @endif

    <title>{{ filled($title = trim(strip_tags($livewire?->getTitle() ?? ''))) ? "{$title} · " : '' }}{{ filament()->getBrandName() }}</title>

    @filamentStyles

    {{ filament()->getTheme()->getHtml() }}
    {{ filament()->getFontHtml() }}
    {{ filament()->getMonoFontHtml() }}

    <style>
        :root {
            --font-family: '{!! filament()->getFontFamily() !!}';
            --mono-font-family: '{!! filament()->getMonoFontFamily() !!}';
            --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};
        }

        html.fi {
            --livewire-progress-bar-color: var(--primary-500);
        }

        [x-cloak] {
            display: none !important;
        }
    </style>

    @stack('styles')

    @if (! filament()->hasDarkMode())
        <script>localStorage.setItem('theme', 'light')</script>
    @elseif (filament()->hasDarkModeForced())
        <script>localStorage.setItem('theme', 'dark')</script>
    @else
        {{-- Before first paint, or the editor flashes white on every load. --}}
        <script>
            const loadDarkMode = () => {
                window.theme = localStorage.getItem('theme') ?? @js(filament()->getDefaultThemeMode()->value)

                document.documentElement.classList.toggle(
                    'dark',
                    window.theme === 'dark' ||
                        (window.theme === 'system' &&
                            window.matchMedia('(prefers-color-scheme: dark)').matches),
                )
            }

            loadDarkMode()
            document.addEventListener('livewire:navigated', loadDarkMode)
        </script>
    @endif
</head>

<body class="fi-body h-dvh overflow-hidden bg-gray-50 text-gray-950 antialiased dark:bg-gray-950 dark:text-white">
    {{ $slot }}

    @livewire(Filament\Livewire\Notifications::class)

    @filamentScripts(withCore: true)

    @stack('scripts')
</body>
</html>
