<?php

namespace App\Filament\Imports;

use App\Models\Payment;
use Filament\Facades\Filament;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class PaymentImporter extends Importer
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('invoice')
                ->relationship(),
            ImportColumn::make('payment_method')
                ->relationship(),
            ImportColumn::make('payment_date')
                ->rules(['date']),
            ImportColumn::make('total')
                ->rules(['max:255']),
            ImportColumn::make('notes')
                ->rules(['max:65535']),
            ImportColumn::make('status')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Payment
    {

        $this->data['team_id'] = Filament::getTenant()->id ;
        return Payment::firstOrNew($this->data);
        // return Payment::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        // return new Payment();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your payment import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
