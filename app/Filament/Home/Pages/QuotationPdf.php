<?php

namespace App\Filament\Home\Pages;

use App\Models\Item;
use App\Models\Team;
use App\Models\Customer;
use Filament\Pages\Page;
use App\Models\Quotation;
use App\Models\TeamSetting;
use Filament\Actions\Action;
use Filament\Facades\Filament;

class QuotationPdf extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'quotation-pdf/{id}';
    protected static string $view = 'filament.home.pages.quotation-pdf';
    protected static bool $shouldRegisterNavigation = false;

    public $quotation = null;
    public $items = null;

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.base';
    }

    public function mount($id = null){
        $id = str_replace('luqmanahmadnordin', "", base64_decode($id));
        $quotation = Quotation::find($id);
        $customer = Customer::where('id', $quotation->customer_id)->first();
        $team = Team::where('id', $quotation->team_id)->first();
        $team_setting = TeamSetting::where('team_id', $quotation->team_id)->first();
        $quotation->logo = $team->photo ;
        $quotation->address = $team->address ;
        $quotation->poscode = $team->poscode ;
        $quotation->city = $team->city ;
        $quotation->state = $team->state ;
        $quotation->to_address = $customer->address ;
        $quotation->to_poscode = $customer->poscode ;
        $quotation->to_city = $customer->city ;
        $quotation->to_state = $customer->state ;
        $quotation->prefix = $team_setting->quotation_prefix_code ?? '#Q';
                    
        $this->items = Item::with('product')->where('quotation_id', $quotation->id)->get();
        $this->quotation = (object)$quotation->toArray();

    }

    public function printAction(): Action
    {
        return Action::make('print')
        ->button()
        ->url('#')
        ->icon('heroicon-m-printer')
        ->color('info')
        ->extraAttributes(['class' => 'printme']);
    }
}
