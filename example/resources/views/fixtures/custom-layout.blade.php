{{-- A host app's own shell, the way a client site would define one. Used by
     CustomLayoutTest to prove the head survives replacing atelier.layout. --}}
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    @include('atelier::partials.meta')
    @include('atelier::partials.tokens')
</head>
<body>
    <header>A host app's own navigation</header>
    <main>{!! $blocks !!}</main>
</body>
</html>
