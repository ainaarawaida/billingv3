<?php

namespace App\Filament\Sys\Resources\RecurringInvoiceResource\Pages;

use App\Filament\Sys\Resources\RecurringInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRecurringInvoice extends ViewRecord
{
    protected static string $resource = RecurringInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
