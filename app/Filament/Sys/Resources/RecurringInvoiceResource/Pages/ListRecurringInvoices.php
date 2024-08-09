<?php

namespace App\Filament\Sys\Resources\RecurringInvoiceResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use App\Models\RecurringInvoice;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;


use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Exports\RecurringInvoiceExporter;
use App\Filament\Imports\RecurringInvoiceImporter;
use App\Filament\Sys\Resources\RecurringInvoiceResource;

class ListRecurringInvoices extends ListRecords
{
    protected static string $resource = RecurringInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(RecurringInvoiceImporter::class)
                ->icon('heroicon-o-arrow-up-on-square')
                ->color('primary'), 
            ExportAction::make()
                ->exporter(RecurringInvoiceExporter::class)
                ->icon('heroicon-o-arrow-down-on-square')
                ->color('primary'), 
        ];
    }

    public function getTabs(): array
    {
      

        return [
            'all' => Tab::make(),
            // ->badge(Invoice::query()->whereBelongsTo(Filament::getTenant(), 'teams')->count()),
            'One Time' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('every', 'One Time')),
            'Daily' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('every', 'Daily')),
            'Monthly' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('every', 'Monthly')),
            'Yearly' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('every', 'Yearly')),
           
        ];
    }
    
}
