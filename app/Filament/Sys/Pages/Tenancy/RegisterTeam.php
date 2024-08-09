<?php

namespace App\Filament\Sys\Pages\Tenancy;

use App\Models\Team;
use App\Models\User;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\TeamSetting;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;

class RegisterTeam extends RegisterTenant
{
      public static function getLabel(): string
      {
            return 'Register Organization';
      }

      public function form(Form $form): Form
      {
            return $form
                  ->schema([
                        TextInput::make('name')
                              ->label('Name / Company Name')
                              ->required()
                              ->live(onBlur:true)
                              ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                              ->required()
                              ->unique(Team::class, 'slug'),
                        TextInput::make('email')
                              ->email()
                              // ->required()
                              ->maxLength(255),
                        TextInput::make('phone')
                              ->tel()
                              // ->required()
                              ->maxLength(255),
                      
                          TextInput::make('ssm')
                              ->label('SSM No.')
                              ->maxLength(255),
                              TextInput::make('address')
                              ->maxLength(255),
                          TextInput::make('poscode')
                              ->maxLength(255),
                          TextInput::make('city')
                              ->maxLength(255),
                          Select::make('state')
                                      ->options([
                                          'JHR' => 'Johor',
                                          'KDH' => 'Kedah',
                                          'KTN' => 'Kelantan',
                                          'MLK' => 'Melaka',
                                          'NSN' => 'Negeri Sembilan',
                                          'PHG' => 'Pahang',
                                          'PRK' => 'Perak',
                                          'PLS' => 'Perlis',
                                          'PNG' => 'Pulau Pinang',
                                          'SBH' => 'Sabah',
                                          'SWK' => 'Sarawak',
                                          'SGR' => 'Selangor',
                                          'TRG' => 'Terengganu',
                                          'KUL' => 'W.P. Kuala Lumpur',
                                          'LBN' => 'W.P. Labuan',
                                          'PJY' => 'W.P. Putrajaya'
                                      ])
                                      ->searchable()
                                      ->preload()
  
               

                  ]);
      }

      protected function handleRegistration(array $data): Team
      {
            $team = Team::create($data);

            $team->members()->attach(auth()->user());

            //First Setting

            TeamSetting::create([
                'team_id' => $team->id,
                'quotation_prefix_code' => '#Q',
                'quotation_current_no' => '0',
                'quotation_template' => '1',
                'invoice_prefix_code' => '#I',
                'invoice_current_no' => '0',
                'invoice_template' => '1',
                'recurring_invoice_prefix_code' => '#RI',
                'recurring_invoice_current_no' => '0',
            ]);

            return $team;
      }

      public static function canView(): bool
      {
        $checkteam = User::where('id', auth()->user()->id)->first()->teams->count();
         if($checkteam > 2){
            return false;
         }
            return true;
      }

      protected function hasFullWidthFormActions(): bool
      {
          return false;
      }

      
    protected function getFormActions(): array
    {
        return [
           
            Action::make('Back')
            ->url(url()->previous())
            ->extraAttributes(['style' => 'width:30%;','class' => 'bg-gray-400']),    
            $this->getRegisterFormAction()
            ->extraAttributes(['style' => 'width:60%;']),   
        ];
    }

      
}