<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['doc_no'] = Invoice::generateDocNo();

        return $data;
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function getTitle(): string
    {
        return 'New Invoice';
    }

    public function getHeading(): string
    {
        return ' ';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewAll')
                ->label('View Invoices')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->size(ActionSize::ExtraSmall)

                ->url(fn() => InvoiceResource::getUrl('index')),
        ];
    }
}
