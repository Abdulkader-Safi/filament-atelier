{{--
    Design tokens as CSS custom properties.

    Include this from your own layout if you replace `atelier.layout`, and put
    it *after* your stylesheet so the tokens win:

        @include('atelier::partials.tokens')

    Skip it and every `var(--atelier-*)` resolves to nothing, which means the
    shared section controls silently do nothing and the RTL font never swaps.
    Nothing errors, which is exactly why this is worth saying out loud.
--}}
<style>{!! \Safi\Atelier\Tokens::css() !!}body{font-family:var(--atelier-font-sans)}</style>
