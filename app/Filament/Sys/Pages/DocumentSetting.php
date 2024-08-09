<?php

namespace App\Filament\Sys\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\TeamSetting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;

class DocumentSetting extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public $settingurl ;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.sys.pages.document-setting';
    protected static ?string $title = 'Document Settings';
    protected static ?string $navigationGroup = 'Setting';
    protected static ?int $navigationSort = 2;
    protected ?string $heading = '';

    public function mount(): void
    {

        $this->settingurl = $this->getUrl() ;

        $teamSetting = TeamSetting::where('team_id', Filament::getTenant()->id )->first()?->toArray();
        $this->form->fill([
            'quotation_prefix_code' => $teamSetting['quotation_prefix_code'] ?? '#Q',
            'quotation_current_no' =>  $teamSetting['quotation_current_no'] ?? 0,
            'quotation_template' => $teamSetting['quotation_template']  ?? 1,
            'invoice_prefix_code' => $teamSetting['invoice_prefix_code']  ?? '#I',
            'invoice_current_no' => $teamSetting['invoice_current_no']  ?? 0,
            'invoice_template' => $teamSetting['invoice_template']  ?? 1,
            'recurring_invoice_prefix_code' => $teamSetting['recurring_invoice_prefix_code']  ?? '#RI',
            'recurring_invoice_current_no' => $teamSetting['recurring_invoice_current_no']  ?? 0,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Quotation')
                            ->schema([
                                // ...
                                TextInput::make('quotation_prefix_code')
                                    ->prefixIcon('heroicon-o-clipboard-document-check')
                                    ->required(),
                                TextInput::make('quotation_current_no')
                                    ->prefixIcon('heroicon-o-clipboard-document-check')
                                    ->numeric()
                                    ->required(),
                                Group::make()
                                    ->schema([
                                        Select::make('quotation_template')
                                            ->prefixIcon('heroicon-o-clipboard-document-check')
                                            ->required()
                                            ->options([
                                                '1' => 'Template 01',
                                            ]),
                                        Placeholder::make('')
                                            ->hiddenLabel()
                                            ->content(fn ($record) => new HtmlString('
                                            <div class="flex flex-col justify-center items-center">
                                                    <img src="'.url('/assets').'/quotationpdf.png" class="" style="width:300px;">
                                              
                                            </div>
                                        
                                        ')),


                                    ])

                                    ->columns(2)
                            ]),
                        Tabs\Tab::make('Invoice')
                            ->schema([
                                // ...
                                 TextInput::make('invoice_prefix_code')
                                    ->prefixIcon('heroicon-o-clipboard-document-check')
                                    ->required(),
                                TextInput::make('invoice_current_no')
                                    ->prefixIcon('heroicon-o-clipboard-document-check')
                                    ->numeric()
                                    ->required(),
                                Group::make()
                                    ->schema([
                                        Select::make('invoice_template')
                                            ->prefixIcon('heroicon-o-clipboard-document-check')
                                            ->required()
                                            ->options([
                                                '1' => 'Template 01',
                                            ]),
                                        Placeholder::make('')
                                            ->hiddenLabel()
                                            ->content(fn ($record) => new HtmlString('
                                            <div class="flex flex-col justify-center items-center">
                                                    <img src="'.url('/assets').'/invoicepdf.png" class="" style="width:300px;">
                                              
                                            </div>
                                        
                                        ')),


                                    ])
                                    ->columns(2)

                               
                            ]),
                        Tabs\Tab::make('Recurring Invoice')
                            ->schema([
                                // ...
                                 TextInput::make('recurring_invoice_prefix_code')
                                    ->prefixIcon('heroicon-o-clipboard-document-check')
                                    ->required(),
                                TextInput::make('recurring_invoice_current_no')
                                    ->prefixIcon('heroicon-o-clipboard-document-check')
                                    ->numeric()
                                    ->required(),
                                

                               
                            ]),
                        Tabs\Tab::make('Payment')
                            ->schema([
                                // ...
                            ]),
                    ])

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

        $data = $this->form->getState();
        try {
            $teamSetting = TeamSetting::updateOrCreate(
                ['team_id' => Filament::getTenant()->id], // Search by email
                $data
            );
        } catch (Halt $exception) {
            return;
        }

        Notification::make() 
        ->success()
        ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
        ->send(); 
        
    }
}
