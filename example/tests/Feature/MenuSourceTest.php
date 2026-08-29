<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Safi\Atelier\Filament\Pages\MenuManager;
use Safi\Atelier\Models\Menu;
use Safi\Atelier\Models\Page;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(fn () => actingAs(User::factory()->create()));

function menuSourcePage(string $title, string $slug): Page
{
    $page = Page::create(['title' => $title, 'layout' => 'marketing', 'draft_content' => []]);
    $page->setSlugs(['en' => $slug, 'ar' => "{$slug}-ar"]);
    $page->publish();

    return $page;
}

it('picks a page as a menu item, with its title and URL copied in, not retyped', function () {
    $about = menuSourcePage('About us', 'about');

    Livewire::test(MenuManager::class)
        ->callAction('add-'.Str::slug(Page::class), data: ['id' => $about->getKey()]);

    $tree = Menu::forLocation('primary')->tree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['label']['en'])->toBe('About us')
        ->and($tree[0]['url'])->toBe('http://localhost:8000/about');
});

it('keeps a picked page menu item working after the page itself is deleted', function () {
    $about = menuSourcePage('About us', 'about');
    menuSourcePage('Home', 'home');

    Livewire::test(MenuManager::class)
        ->callAction('add-'.Str::slug(Page::class), data: ['id' => $about->getKey()]);

    // The item is a snapshot, not a foreign key, so deleting the source page
    // changes nothing about the menu: no dangling reference to null-check,
    // no 500 on the public page that renders it.
    $about->delete();

    $html = get('/home')->assertOk()->getContent();

    expect($html)->toContain('About us')
        ->and($html)->toContain('href="http://localhost:8000/about"');
});
