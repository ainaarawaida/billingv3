<?php

namespace App\Filament\Imports;

use App\Models\Invoice;
use Filament\Facades\Filament;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class InvoiceImporter extends Importer
{
    protected static ?string $model = Invoice::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('customer')
                ->relationship(),
            ImportColumn::make('numbering')
                ->rules(['max:255']),
            ImportColumn::make('invoice_date')
                ->rules(['date']),
            ImportColumn::make('pay_before')
                ->rules(['date']),
            ImportColumn::make('invoice_status')
                ->rules(['max:255']),
            ImportColumn::make('title')
                ->rules(['max:255']),
            ImportColumn::make('notes')
                ->rules(['max:65535']),
            ImportColumn::make('sub_total')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('taxes')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('percentage_tax')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('delivery')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('final_amount')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('invoice_type'),
            ImportColumn::make('payment_type'),
        ];
    }

    public function resolveRecord(): ?Invoice
    {
        $this->data['team_id'] = Filament::getTenant()->id ;
        return Invoice::firstOrNew($this->data);
        // return Invoice::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        // return new Invoice();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your invoice import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
