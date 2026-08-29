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

it('seeds the bound registry from config, the same source locales reads from', function () {
    $registry = app(MenuRegistry::class);

    // From example/config/atelier.php, not from a panel provider call.
    expect($registry->has('primary'))->toBeTrue()
        ->and($registry->has('sidebar'))->toBeTrue()
        ->and($registry->label('sidebar'))->toBe('Sidebar');
});

it('exposes a plain PHP entry point for a location\'s tree, for anything that is not the shipped partial', function () {
    Menu::forLocation('primary')->update(['items' => [
        ['id' => 'm_x', 'label' => ['en' => 'X'], 'url' => '/x', 'target' => '_self', 'children' => []],
    ]]);

    expect(Menu::treeFor('primary'))->toHaveCount(1)
        ->and(Menu::treeFor('nobody-registered-this'))->toBe([]);
});

it('resolves a label for a locale, falling back to the default locale', function () {
    $item = ['label' => ['en' => 'English only']];

    expect(Menu::label($item, 'en'))->toBe('English only')
        ->and(Menu::label($item, 'ar'))->toBe('English only')
        ->and(Menu::label(['label' => []], 'en'))->toBe('');
});
