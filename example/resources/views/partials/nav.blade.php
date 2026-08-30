{{--
    A normal view in this app. Menu::treeFor() and Menu::label() are the only
    things borrowed from Atelier; everything below is yours to restyle.

        @include('partials.nav', ['location' => 'primary'])
--}}
@php
    $navLocale = $locale ?? app()->getLocale();
    $navItems = \Safi\Atelier\Models\Menu::treeFor($location);
    $navCurrentPath = '/'.trim(request()->path(), '/');
@endphp
@if (! empty($navItems))
    <ul class="flex flex-wrap items-center gap-6 text-sm">
        @foreach ($navItems as $item)
            @php
                $url = $item['url'] ?? null;
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
                                <a href="{{ $child['url'] ?: '#' }}" target="{{ $child['target'] ?? '_self' }}">
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
