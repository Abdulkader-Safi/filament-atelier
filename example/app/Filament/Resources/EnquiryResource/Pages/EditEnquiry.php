<?php

declare(strict_types=1);

namespace App\Filament\Resources\EnquiryResource\Pages;

use App\Filament\Resources\EnquiryResource;
use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEnquiry extends EditRecord
{
    protected static string $resource = EnquiryResource::class;

    public function getTitle(): string
    {
        /** @var Enquiry $record */
        $record = $this->record;

        return $record->page_title.' for '.$record->name;
    }

    protected function getHeaderActions(): array
    {
        /** @var Enquiry $record */
        $record = $this->record;

        return [
            Action::make('reply')
                ->label('Reply by email')
                ->icon('heroicon-m-envelope')
                ->color('gray')
                ->url(fn () => 'mailto:'.$record->email.'?subject='.rawurlencode('Re: '.$record->page_title))
                ->openUrlInNewTab(),

            Action::make('open')
                ->label('Open the page')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $record->page?->url(array_key_first(config('atelier.locales'))))
                ->openUrlInNewTab()
                ->visible(fn () => $record->page?->isPublished() ?? false),

            DeleteAction::make(),
        ];
    }
}
