<?php

namespace App\Filament\Exports;

use App\Models\Invoice;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class InvoiceExporter extends Exporter
{
    protected static ?string $model = Invoice::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('customer.name'),
            ExportColumn::make('team_id'),
            ExportColumn::make('numbering'),
            ExportColumn::make('invoice_date'),
            ExportColumn::make('pay_before'),
            ExportColumn::make('invoice_status'),
            ExportColumn::make('title'),
            ExportColumn::make('notes'),
            ExportColumn::make('sub_total'),
            ExportColumn::make('taxes'),
            ExportColumn::make('percentage_tax'),
            ExportColumn::make('delivery'),
            ExportColumn::make('final_amount'),
            ExportColumn::make('invoice_type'),
            ExportColumn::make('payment_type'),
            ExportColumn::make('deleted_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your invoice export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
