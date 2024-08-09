<?php

namespace App\Filament\Sys\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Sys\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(ProductImporter::class)
                ->icon('heroicon-o-arrow-up-on-square')
                ->color('primary'), 
            ExportAction::make()
                ->exporter(ProductExporter::class)
                ->icon('heroicon-o-arrow-down-on-square')
                ->color('primary'), 
        ];
    }
}
