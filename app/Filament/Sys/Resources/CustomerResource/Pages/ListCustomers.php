<?php

namespace App\Filament\Sys\Resources\CustomerResource\Pages;

use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\CustomerExporter;
use App\Filament\Imports\CustomerImporter;
use App\Filament\Sys\Resources\CustomerResource;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(CustomerImporter::class)
                ->icon('heroicon-o-arrow-up-on-square')
                ->color('primary'), 
            ExportAction::make()
                ->exporter(CustomerExporter::class)
                ->icon('heroicon-o-arrow-down-on-square')
                ->color('primary'), 
        ];
    }
}
