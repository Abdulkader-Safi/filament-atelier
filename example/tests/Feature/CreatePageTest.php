<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Safi\Atelier\Filament\Resources\PageResource\Pages\ListPages;
use Safi\Atelier\Models\Page;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('creates a page from the panel, with its slugs', function () {
    Livewire::test(ListPages::class)
        ->callAction('create', data: [
            'title' => 'Brand new page',
            'slugs' => ['en' => 'brand-new', 'ar' => 'brand-new-ar'],
        ])
        ->assertHasNoActionErrors();

    $page = Page::where('title', 'Brand new page')->firstOrFail();

    expect($page->slug('en'))->toBe('brand-new')
        ->and($page->slug('ar'))->toBe('brand-new-ar');
});

it('creates a page when no slug is typed, generating one from the title', function () {
    Livewire::test(ListPages::class)
        ->callAction('create', data: ['title' => 'Our Services'])
        ->assertHasNoActionErrors();

    expect(Page::where('title', 'Our Services')->firstOrFail()->slug('en'))->toBe('our-services');
});

it('edits slugs from the settings screen without duplicating rows', function () {
    $page = Page::create(['title' => 'Editable']);
    $page->setSlugs(['en' => 'first', 'ar' => 'first-ar']);

    Livewire::test(Safi\Atelier\Filament\Resources\PageResource\Pages\EditPageSettings::class, ['record' => $page->getKey()])
        ->assertSet('data.slugs.en', 'first')
        ->fillForm(['slugs' => ['en' => 'second', 'ar' => 'second-ar']])
        ->call('save')
        ->assertHasNoFormErrors();

    $page->refresh()->unsetRelation('slugs');

    expect($page->slug('en'))->toBe('second')
        ->and($page->slugs()->count())->toBe(2);
});
