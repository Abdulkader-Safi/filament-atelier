{{--
    Renders one location. Include it from a layout or a header/footer block:

        @include('atelier::partials.menu', ['location' => 'primary', 'locale' => $locale])

    `locale` defaults to the app locale, which is right for a preview or a
    public render where the route already set it, and wrong for anything
    rendered outside that request cycle.

    Renders nothing for an empty or unregistered location, on purpose: a
    location a client hasn't filled in yet should not put an empty <nav> in
    the DOM, and a location nobody registered should not 500 a page that
    merely names it, the same reasoning `LayoutRegistry::view()` already
    applies to a missing layout.

    Building your own markup instead of overriding this file? Skip the
    partial and call `Safi\Atelier\Models\Menu::treeFor($location)` directly
    from a controller, a Livewire component, or any other Blade view. It's
    the same call this file makes.
--}}
@php
    $menuLocale = $locale ?? app()->getLocale();
    $menuTree = \Safi\Atelier\Models\Menu::treeFor($location);
@endphp
@if (! empty($menuTree))
    <nav>
        @include('atelier::partials.menu-items', ['items' => $menuTree, 'menuLocale' => $menuLocale])
    </nav>
@endif
