<?php

declare(strict_types=1);

namespace App\Blocks;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Blocks\BaseBlock;

/**
 * The form that turns a page into a lead.
 *
 * On a service it offers that service's tiers; on a product, its variations
 * and a quantity; anywhere else it is a plain message. All three post to the
 * app's own /request route and land in Requests in the panel.
 */
class RequestFormBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'request-form';
    }

    public static function label(): string
    {
        return 'Request form';
    }

    public static function icon(): string
    {
        return 'heroicon-o-inbox-arrow-down';
    }

    public static function category(): string
    {
        return 'Selling';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function translatable(): array
    {
        return ['heading', 'subheading', 'button', 'note'];
    }

    public static function defaults(): array
    {
        return [
            'heading' => ['en' => 'Book this service'],
            'subheading' => ['en' => 'Tell us what you need and we will confirm a time within one working day.'],
            'button' => ['en' => 'Send the request'],
            'note' => ['en' => 'No payment now. We confirm the price before anyone turns up.'],
        ];
    }

    public static function view(): string
    {
        return 'blocks.request-form';
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->live(debounce: 400),
            TextInput::make('subheading')->live(debounce: 400),
            TextInput::make('button')->label('Button label')->live(debounce: 400),
            Textarea::make('note')->label('Small print')->rows(2)->live(debounce: 400),
        ];
    }
}
