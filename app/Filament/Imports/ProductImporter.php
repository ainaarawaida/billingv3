<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('title')
                ->rules(['max:65535']),
            ImportColumn::make('tax')
                ->rules(['max:255']),
            ImportColumn::make('quantity')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('price')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Product
    {
        $this->data['team_id'] = Filament::getTenant()->id ;
        return Product::firstOrNew($this->data);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
