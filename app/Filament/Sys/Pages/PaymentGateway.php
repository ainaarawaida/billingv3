<?php

namespace App\Filament\Sys\Pages;


use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\TeamSetting;
use App\Models\UserSetting;
use Filament\Actions\Action;
use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;

class PaymentGateway extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Setting';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.sys.pages.payment-gateway';

    
    public function mount(): void
    {
        $teamSetting = TeamSetting::where('team_id', Filament::getTenant()->id )->first()?->toArray();
        $this->form->fill($teamSetting['payment_gateway'] ?? []);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Securepay')
                            ->visible(false)
                            ->schema([
                                // ...
                                Placeholder::make('created')
                                    ->hiddenLabel()
                                    ->content(fn ($record) => new HtmlString('<div class="text-center">
                                    <div class="flex flex-col justify-center items-center">
                                        <a href="https://securepay.my" target="_blank">
                                            <img src="'.url('/assets').'/securepay.jpg" class="w-70">
                                        </a>
                                        <a href="https://securepay.my" target="_blank" >
                                            <strong>Belum ada akaun SecurePay?</strong>
                                        </a><br>
                                        <a href="https://securepay.my" target="_blank" class="bg-blue-500 text-white py-2 px-4 rounded" style="background-color:#4069db; color: #FFF; margin-top: 10px;">
                                            <i class="fa fa-check"></i> &nbsp; Daftar Sekarang
                                        </a>
                                    </div>
                                    
                                </div>')),
                                Hidden::make('Securepay.id')
                                    ->default(1)
                                    ->formatStateUsing(fn (?string $state): ?string => 1),
                                Hidden::make('Securepay.name')
                                    ->default('Securepay')
                                    ->formatStateUsing(fn (?string $state): ?string => 'Securepay'),
                                Toggle::make('Securepay.status')
                                    ->onIcon('heroicon-o-check')
                                    ->offIcon('heroicon-o-x-mark')
                                    ->onColor('success')
                                    ->offColor('danger'),
                              
                                TextInput::make('Securepay.sp_SecurePay_UID')
                                    ->label('SecurePay UID')
                                    ->prefixIcon('heroicon-o-clipboard-document-check'),
                                TextInput::make('Securepay.sp_Authentication_Token')
                                    ->label('Authentication Token')
                                    ->prefixIcon('heroicon-o-clipboard-document-check'),
                                TextInput::make('Securepay.sp_Checksum_Token')
                                    ->label('Checksum Token')
                                    ->prefixIcon('heroicon-o-clipboard-document-check'),
                            ]),
                        Tabs\Tab::make('Toyyibpay')
                            ->schema([
                                Placeholder::make('created')
                                    ->hiddenLabel()
                                    ->content(fn ($record) => new HtmlString('<div class="text-center">
                                    <div class="flex flex-col justify-center items-center">
                                        <a href="https://toyyibpay.com/" target="_blank">
                                            <img src="'.url('/assets').'/toyyibpay.jpg" class="w-70">
                                        </a>
                                        <a href="https://toyyibpay.com/" target="_blank" >
                                            <strong>Belum ada akaun toyyibpay?</strong>
                                        </a><br>
                                        <a href="https://toyyibpay.com/" target="_blank" class="bg-blue-500 text-white py-2 px-4 rounded" style="background-color:#4069db; color: #FFF; margin-top: 10px;">
                                            <i class="fa fa-check"></i> &nbsp; Daftar Sekarang
                                        </a>
                                    </div>
                                    
                                </div>')),
                                Hidden::make('Toyyibpay.id')
                                    ->default(2)
                                    ->formatStateUsing(fn (?string $state): ?string => 2),
                                Hidden::make('Toyyibpay.name')
                                    ->default('Toyyibpay')
                                    ->formatStateUsing(fn (?string $state): ?string => 'Toyyibpay'),
                                Toggle::make('Toyyibpay.status')
                                    ->onIcon('heroicon-o-check')
                                    ->offIcon('heroicon-o-x-mark')
                                    ->onColor('success')
                                    ->offColor('danger'),
                                TextInput::make('Toyyibpay.tp_ToyyibPay_User_Secret_Key')
                                    ->label('ToyyibPay User Secret Key')
                                    ->prefixIcon('heroicon-o-clipboard-document-check'),
                                TextInput::make('Toyyibpay.tp_ToyyibPay_categoryCode')
                                    ->label('ToyyibPay Category Code')
                                    ->prefixIcon('heroicon-o-clipboard-document-check'),
                                Toggle::make('Toyyibpay.billChargeToCustomer')
                                    ->label('Bill Charge To Customer')
                                    ->onIcon('heroicon-o-check')
                                    ->offIcon('heroicon-o-x-mark')
                                    ->onColor('success')
                                    ->offColor('danger'),
                                Section::make('Test Sandbox')
                                    ->description('If you want to use sandbox mode, please fill up the form below.')
                                    ->schema([
                                        Toggle::make('Toyyibpay.sandbox')
                                            ->onIcon('heroicon-o-check')
                                            ->offIcon('heroicon-o-x-mark')
                                            ->onColor('success')
                                            ->offColor('danger'),
                                        TextInput::make('Toyyibpay.tp_ToyyibPay_Sandbox_User_Secret_Key')
                                            ->label('ToyyibPay Sandbox User Secret Key')
                                            ->prefixIcon('heroicon-o-clipboard-document-check'),
                                        TextInput::make('Toyyibpay.tp_ToyyibPay_Sandbox_categoryCode')
                                            ->label('ToyyibPay Sandbox Category Code')
                                            ->prefixIcon('heroicon-o-clipboard-document-check'),
                                        // ...
                                    ])
                             
                            ]),
                        // Tabs\Tab::make('Tab 3')
                        //     ->schema([
                        //         // ...
                        //     ]),
                        ]),

            ])
            ->columns(1)
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {

        $temp = $this->form->getState();
        $data['payment_gateway'] = $temp ; 
        try {
            $teamSetting = TeamSetting::updateOrCreate(
                ['team_id' => Filament::getTenant()->id], // Search by email
                $data
            );
        } catch (Halt $exception) {
            return;
        }

        //update all payment method if payment gateway changed
        foreach($temp AS $key => $val){
            if($val['status'] == false){
                PaymentMethod::where('team_id', Filament::getTenant()->id)
                ->where('payment_gateway_id', $val['id'])
                ->update(['status' => 0]);
            }

        }


        Notification::make() 
        ->success()
        ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
        ->send(); 
        
    }
}
