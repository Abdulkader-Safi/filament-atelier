<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Safi\Atelier\EnglishFallbackLoader;

const PUBLISH_KEY = 'filament-atelier::atelier.pages.page_editor.publish';

it('shows a shipped language in that language', function () {
    App::setLocale('fr');

    expect(__(PUBLISH_KEY))->toBe(trans(PUBLISH_KEY, [], 'fr'))
        ->and(__(PUBLISH_KEY))->not->toBe(PUBLISH_KEY)
        ->and(__(PUBLISH_KEY))->not->toBe(trans(PUBLISH_KEY, [], 'en'));
});

it('falls back to English for a language Atelier does not ship, whatever the app fallback is', function () {
    // The case that showed raw keys: the panel in German, and the app's own
    // fallback a language Atelier has no file for either.
    app('translator')->setFallback('ar');
    App::setLocale('de');

    expect(__(PUBLISH_KEY))->toBe('Publish');
});

it('fills a key missing from a shipped language with English', function () {
    // A French file that predates a newer key, like a host's published copy.
    $inner = (new ArrayLoader)
        ->addMessages('en', 'atelier', ['publish' => 'Publish', 'new_key' => 'New'], 'filament-atelier')
        ->addMessages('fr', 'atelier', ['publish' => 'Publier'], 'filament-atelier');

    $translator = new Translator(new EnglishFallbackLoader($inner), 'fr');
    $translator->setFallback('ar');

    expect($translator->get('filament-atelier::atelier.publish'))->toBe('Publier')
        ->and($translator->get('filament-atelier::atelier.new_key'))->toBe('New');
});

it('leaves the host app strings alone', function () {
    app('translator')->setFallback('ar');
    App::setLocale('de');

    // Not Atelier's namespace, so no English fallback: Laravel's usual behaviour.
    expect(__('validation.required', ['attribute' => 'x']))->toBe('validation.required');
});
