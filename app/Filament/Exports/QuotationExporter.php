<?php

namespace App\Filament\Exports;

use App\Models\Quotation;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class QuotationExporter extends Exporter
{
    protected static ?string $model = Quotation::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('customer.name'),
            ExportColumn::make('team_id'),
            ExportColumn::make('numbering'),
            ExportColumn::make('quotation_date'),
            ExportColumn::make('valid_days'),
            ExportColumn::make('quote_status'),
            ExportColumn::make('summary'),
            ExportColumn::make('sub_total'),
            ExportColumn::make('taxes'),
            ExportColumn::make('percentage_tax'),
            ExportColumn::make('delivery'),
            ExportColumn::make('final_amount'),
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
        $body = 'Your quotation export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
