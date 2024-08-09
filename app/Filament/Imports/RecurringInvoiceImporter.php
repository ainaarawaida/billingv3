<?php

namespace App\Filament\Imports;

use Filament\Facades\Filament;
use App\Models\RecurringInvoice;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class RecurringInvoiceImporter extends Importer
{
    protected static ?string $model = RecurringInvoice::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('team_id')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('customer')
                ->relationship(),
            ImportColumn::make('numbering')
                ->rules(['max:255']),
            ImportColumn::make('summary'),
            ImportColumn::make('start_date')
                ->rules(['date']),
            ImportColumn::make('stop_date')
                ->rules(['date']),
            ImportColumn::make('every')
                ->rules(['max:255']),
            ImportColumn::make('status')
                ->rules(['max:255']),
            ImportColumn::make('terms_conditions'),
            ImportColumn::make('footer'),
            ImportColumn::make('attachments'),
        ];
    }

    public function resolveRecord(): ?RecurringInvoice
    {
        // return RecurringInvoice::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return RecurringInvoice::firstOrNew([
            // Update existing records, matching them by `$this->data['column_name']`
            'id' => $this->data['id'] ?? null,
        ]);


        // return new RecurringInvoice();
    }

    protected function beforeSave()
    {
        $this->record->team_id = Filament::getTenant()->id ;
        // Similar to `beforeSave()`, but only runs when creating a new record.
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your recurring invoice import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
