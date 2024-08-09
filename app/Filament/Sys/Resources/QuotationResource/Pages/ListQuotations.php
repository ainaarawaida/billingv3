<?php

namespace App\Filament\Sys\Resources\QuotationResource\Pages;

use Filament\Actions;
use App\Models\Quotation;
use Filament\Facades\Filament;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Exports\QuotationExporter;
use App\Filament\Imports\QuotationImporter;
use App\Filament\Sys\Resources\QuotationResource;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;



    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(QuotationImporter::class)
                ->icon('heroicon-o-arrow-up-on-square')
                ->color('primary'), 
            ExportAction::make()
                ->exporter(QuotationExporter::class)
                ->icon('heroicon-o-arrow-down-on-square')
                ->color('primary'), 
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'draft' => Tab::make()
                // ->badge(Quotation::query()->where('team_id', Filament::getTenant()->id)
                // ->where('quote_status', 'draft')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('quote_status', 'draft')),
            'new' => Tab::make()
                ->badge(Quotation::query()->where('team_id', Filament::getTenant()->id)
                ->where('quote_status', 'new')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('quote_status', 'new')),
            'process' => Tab::make()
                // ->badge(Quotation::query()->where('team_id', Filament::getTenant()->id)
                // ->where('quote_status', 'process')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('quote_status', 'process')),
            'done' => Tab::make()
                // ->badge(Quotation::query()->where('team_id', Filament::getTenant()->id)
                // ->where('quote_status', 'done')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('quote_status', 'done')),
            'expired' => Tab::make()
                // ->badge(Quotation::query()->where('team_id', Filament::getTenant()->id)
                // ->where('quote_status', 'expired')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('quote_status', 'expired')),
            'cancelled' => Tab::make()
                // ->badge(Quotation::query()->where('team_id', Filament::getTenant()->id)
                // ->where('quote_status', 'cancelled')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('quote_status', 'cancelled')),
               
        ];
    }
}
