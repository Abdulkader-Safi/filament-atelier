<?php

declare(strict_types=1);

use App\Models\User;
use Safi\Atelier\ExperimentalFeatures;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('defaults every flag to off', function () {
    expect((new ExperimentalFeatures)->enabled('menus'))->toBeFalse()
        ->and((new ExperimentalFeatures)->enabled('anything-nobody-set'))->toBeFalse();
});

it('turns a flag on, and back off, on the same instance', function () {
    $features = new ExperimentalFeatures;

    $features->set(['menus' => true]);
    expect($features->enabled('menus'))->toBeTrue();

    $features->set(['menus' => false]);
    expect($features->enabled('menus'))->toBeFalse();
});

it('is seeded from config, the same source locales and menus read from', function () {
    // example/config/atelier.php ships this off; AdminPanelProvider's own
    // ->experimental(['menus' => true]) is what turns it on, proving both
    // halves (config default, plugin override) actually wire together.
    expect(app(ExperimentalFeatures::class)->enabled('menus'))->toBeTrue();
});

it('registers the Menus page and its route when the flag is on', function () {
    // Filament builds a panel's page list once, from the plugin
    // registration a provider's boot produced, not fresh on every request:
    // flipping the flag here wouldn't pull an already-registered route,
    // the same way it wouldn't in a real running app either. What's
    // testable is the state example/'s own provider actually produced, on
    // by its own ->experimental(['menus' => true]) call.
    actingAs(User::factory()->create());

    get('/admin/menu-manager')->assertOk();
});
