<?php

namespace App\Filament\Sys\Resources\InvoiceResource\Pages;

use Filament\Actions;
use App\Models\Invoice;
use Filament\Facades\Filament;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Components\Tab;
use App\Filament\Exports\InvoiceExporter;
use App\Filament\Imports\InvoiceImporter;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Sys\Resources\InvoiceResource;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(InvoiceImporter::class)
                ->icon('heroicon-o-arrow-up-on-square')
                ->color('primary'), 
            ExportAction::make()
                ->exporter(InvoiceExporter::class)
                ->icon('heroicon-o-arrow-down-on-square')
                ->color('primary'), 
        ];
    }

    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'draft' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('invoice_status', 'draft')),
            'new' => Tab::make()
                ->badge(Invoice::query()->where('team_id', Filament::getTenant()->id)
                ->where('invoice_status', 'new')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('invoice_status', 'new')),
            'processing' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('invoice_status', 'processing')),
            'done' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('invoice_status', 'done')),
            'expired' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('invoice_status', 'expired')),
            'cancelled' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('invoice_status', 'cancelled')),
               
        ];
    }
}
