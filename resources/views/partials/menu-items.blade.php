{{--
    One level of a menu tree, recursing into `children`. Flex row rather than
    `left`/`right` spacing, so it mirrors correctly under `dir="rtl"` without
    a second stylesheet: row-direction flexbox already reverses under RTL per
    the CSS spec, the same reasoning every block view already follows (03).
--}}
@php
    $currentPath = '/'.trim(request()->path(), '/');
@endphp
<ul class="flex flex-wrap items-center gap-6">
    @foreach ($items as $item)
        @php
            $label = \Safi\Atelier\Models\Menu::label($item, $menuLocale);
            $url = $item['url'] ?? null;
            $itemPath = $url ? '/'.trim((string) parse_url($url, PHP_URL_PATH), '/') : null;
            $isCurrent = $itemPath !== null && $itemPath === $currentPath;
            $isAncestor = ! $isCurrent && $itemPath !== null && $itemPath !== '/'
                && str_starts_with($currentPath.'/', $itemPath.'/');
            $children = $item['children'] ?? [];
        @endphp
        <li>
            <a
                href="{{ $url ?: '#' }}"
                target="{{ $item['target'] ?? '_self' }}"
                @if ($isCurrent) aria-current="page" @endif
                @class(['font-semibold' => $isCurrent || $isAncestor])
            >{{ $label }}</a>

            @if (! empty($children))
                @include('atelier::partials.menu-items', ['items' => $children, 'menuLocale' => $menuLocale])
            @endif
        </li>
    @endforeach
</ul>
