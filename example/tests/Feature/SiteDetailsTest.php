<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Safi\Atelier\Filament\Pages\SiteDetails;
use Safi\Atelier\Models\SiteSettings;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('saves site details from the panel', function () {
    Livewire::test(SiteDetails::class)
        ->fillForm([
            'name' => ['en' => 'dsrpt', 'ar' => 'دسرابت'],
            'description' => ['en' => 'We build websites.'],
            'type' => 'ProfessionalService',
            'telephone' => '+971 4 000 0000',
            'address' => ['locality' => 'Dubai', 'country' => 'AE'],
            'same_as' => [['url' => 'https://www.linkedin.com/company/dsrpt']],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(SiteSettings::get('type'))->toBe('ProfessionalService')
        ->and(SiteSettings::get('telephone'))->toBe('+971 4 000 0000')
        ->and(SiteSettings::get('address.locality'))->toBe('Dubai')
        ->and(SiteSettings::get('same_as'))->toBe(['https://www.linkedin.com/company/dsrpt']);
});

it('keeps one row however often it is saved', function () {
    Livewire::test(SiteDetails::class)->fillForm(['type' => 'Store'])->call('save');
    Livewire::test(SiteDetails::class)->fillForm(['type' => 'Restaurant'])->call('save');

    expect(SiteSettings::count())->toBe(1)
        ->and(SiteSettings::get('type'))->toBe('Restaurant');
});

it('collapses a translated value to a locale, falling back to the default', function () {
    SiteSettings::current()->update(['data' => [
        'name' => ['en' => 'dsrpt', 'ar' => 'دسرابت'],
        'description' => ['en' => 'English only'],
    ]]);

    expect(SiteSettings::translated('name', 'ar'))->toBe('دسرابت')
        ->and(SiteSettings::translated('name', 'en'))->toBe('dsrpt')
        // No Arabic description, so the default locale's fills in.
        ->and(SiteSettings::translated('description', 'ar'))->toBe('English only')
        ->and(SiteSettings::translated('nothing', 'en'))->toBeNull();
});

it('treats a blank value as absent', function () {
    SiteSettings::current()->update(['data' => ['telephone' => '', 'email' => null]]);

    expect(SiteSettings::get('telephone'))->toBeNull()
        ->and(SiteSettings::get('email', 'fallback'))->toBe('fallback');
});
