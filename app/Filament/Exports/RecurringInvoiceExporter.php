<?php

namespace App\Filament\Exports;

use App\Models\RecurringInvoice;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class RecurringInvoiceExporter extends Exporter
{
    protected static ?string $model = RecurringInvoice::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('team_id'),
            ExportColumn::make('customer.name'),
            ExportColumn::make('numbering'),
            ExportColumn::make('summary'),
            ExportColumn::make('start_date'),
            ExportColumn::make('stop_date'),
            ExportColumn::make('every'),
            ExportColumn::make('status'),
            ExportColumn::make('terms_conditions'),
            ExportColumn::make('footer'),
            ExportColumn::make('attachments'),
            ExportColumn::make('deleted_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your recurring invoice export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
