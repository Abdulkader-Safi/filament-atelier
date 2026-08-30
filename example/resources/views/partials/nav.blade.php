{{--
    A normal view in this app. Menu::treeFor(), Menu::label() and Menu::url()
    are the only things borrowed from Atelier; everything below is yours to
    restyle.

        @include('partials.nav', ['location' => 'primary'])
--}}
@php
    $navLocale = $locale ?? app()->getLocale();
    $navItems = \Safi\Atelier\Models\Menu::treeFor($location);
    $navCurrentPath = '/'.trim(request()->path(), '/');
@endphp
{{-- Uncomment to see the raw tree Menu::treeFor() returns. --}}
{{-- <pre class="text-xs">{{ json_encode($navItems, JSON_PRETTY_PRINT) }}</pre> --}}

@if (! empty($navItems))
    <ul class="flex flex-wrap items-center gap-6 text-sm">
        @foreach ($navItems as $item)
            @php
                $url = \Safi\Atelier\Models\Menu::url($item, $navLocale);
                $isCurrent = $url && '/'.trim((string) parse_url($url, PHP_URL_PATH), '/') === $navCurrentPath;
            @endphp
            <li>
                <a
                    href="{{ $url ?: '#' }}"
                    target="{{ $item['target'] ?? '_self' }}"
                    @if ($isCurrent) aria-current="page" @endif
                    @class(['font-semibold' => $isCurrent])
                >{{ \Safi\Atelier\Models\Menu::label($item, $navLocale) }}</a>

                @if (! empty($item['children']))
                    <ul class="mt-1 flex gap-4 ps-4 text-neutral-500">
                        @foreach ($item['children'] as $child)
                            <li>
                                <a href="{{ \Safi\Atelier\Models\Menu::url($child, $navLocale) ?: '#' }}" target="{{ $child['target'] ?? '_self' }}">
                                    {{ \Safi\Atelier\Models\Menu::label($child, $navLocale) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
@endif
