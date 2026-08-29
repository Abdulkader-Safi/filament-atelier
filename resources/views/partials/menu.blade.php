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
--}}
@php
    $menuLocale = $locale ?? app()->getLocale();

    // A location nobody registered shouldn't write a stray row every time a
    // page renders. Menu::forLocation() creates on first read, which is
    // right for the editor (its location came from the registry-built
    // select) and wrong here, where $location is whatever a Blade file
    // happens to pass.
    $menuTree = app(\Safi\Atelier\MenuRegistry::class)->has($location)
        ? \Safi\Atelier\Models\Menu::forLocation($location)->tree()
        : [];
@endphp
@if (! empty($menuTree))
    <nav>
        @include('atelier::partials.menu-items', ['items' => $menuTree, 'menuLocale' => $menuLocale])
    </nav>
@endif
