<?php

namespace App\Filament\Sys\Resources\PaymentResource\Pages;

use Filament\Actions;
use App\Models\Payment;
use Filament\Facades\Filament;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Components\Tab;
use App\Filament\Exports\PaymentExporter;
use App\Filament\Imports\PaymentImporter;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Sys\Resources\PaymentResource;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(PaymentImporter::class)
                ->icon('heroicon-o-arrow-up-on-square')
                ->color('primary'), 
            ExportAction::make()
                ->exporter(PaymentExporter::class)
                ->icon('heroicon-o-arrow-down-on-square')
                ->color('primary'), 
        ];
    }

    public function getTabs(): array
    {

        return [
            'all' => Tab::make(),
            // ->badge(Invoice::query()->whereBelongsTo(Filament::getTenant(), 'teams')->count()),
            'draft' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),
            'pending_payment' => Tab::make()
                ->badge(Payment::query()->where('team_id', Filament::getTenant()->id)
                ->where('status', 'pending_payment')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_payment')),
            'on_hold' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'on_hold')),
            'processing' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing')),
            'completed' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            'failed' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed')),
            'cancelled' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
            'refunded' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'refunded')),
              
        ];
    }
}
