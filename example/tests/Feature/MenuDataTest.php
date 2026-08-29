<?php

declare(strict_types=1);

use Safi\Atelier\MenuRegistry;
use Safi\Atelier\Models\Menu;

it('takes a label on its own and defaults depth to one', function () {
    $registry = new MenuRegistry;

    $registry->locations([
        'primary' => 'Primary',
        'footer' => ['label' => 'Footer', 'depth' => 2],
        'social' => [],
    ]);

    expect($registry->options())->toBe(['primary' => 'Primary', 'footer' => 'Footer', 'social' => 'Social'])
        ->and($registry->depth('primary'))->toBe(1)
        ->and($registry->depth('footer'))->toBe(2)
        ->and($registry->has('primary'))->toBeTrue()
        ->and($registry->has('nope'))->toBeFalse();
});

it('refuses a source that does not implement MenuSource', function () {
    $registry = new MenuRegistry;

    expect(fn () => $registry->sources(Menu::class))
        ->toThrow(InvalidArgumentException::class);
});

it('creates an empty row the first time a location is opened, and reuses it after', function () {
    $menu = Menu::forLocation('primary');

    expect($menu->tree())->toBe([])
        ->and(Menu::forLocation('primary')->getKey())->toBe($menu->getKey());
});
