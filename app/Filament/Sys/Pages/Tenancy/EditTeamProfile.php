<?php

namespace App\Filament\Sys\Pages\Tenancy;

use App\Models\Team;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Forms\Concerns\InteractsWithForms;

class EditTeamProfile extends EditTenantProfile
{

      // use InteractsWithForms;
      
      public ?array $data = [];

      public static function getLabel(): string
      {
            return 'Organization';
      }

      public function form(Form $form): Form
      {
            return $form
            ->schema([
                  Tabs::make('Tabs')
                        ->tabs([
                              Tabs\Tab::make('general')
                                    ->label(__("General"))
                                    ->schema([
                                          TextInput::make('name')
                                                ->label('Name / Company Name')
                                                ->required()
                                                ->live(onBlur:true)
                                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                                          TextInput::make('slug')
                                                ->required()
                                                ->unique(Team::class, ignoreRecord: true),
                                          TextInput::make('email')
                                                ->email()
                                                // ->required()
                                                ->maxLength(255),
                                          TextInput::make('phone')
                                                ->tel()
                                                // ->required()
                                                ->maxLength(255),
                                        
                                    ])->columns(2),
                              Tabs\Tab::make('address')
                                    ->label(__("Address"))
                                    ->schema([
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
                        
                              
                                    ])->columns(2),
                              Tabs\Tab::make('logo')
                                    ->label(__("Logo"))
                                    ->schema([
                                          FileUpload::make('photo')
                                                ->image()
                                                ->directory('photo')
                                                ->avatar()
                                                ->imageEditor()
                                                ->circleCropper()
                                    ]),
                        ])
            ]);


           
      }

      protected function getRedirectUrl(): ?string
      {
            // return route(self::getRouteName());
            // return self::getUrl();
            // return self::getUrl();
                  $record = $this->form->getState();
            //     return null;
                 // return Filament::getUrl('index');
                  // $newSlug = $this->record->slug;
                  $panel = Filament::getCurrentPanel()->getId();
                  // dd("d",$tenant, $this->form->getState()['slug']);
                  // Replace '/desired-route' with the actual path you want to redirect to
                  // dd(route('filament.admin.tenant'));
                  // dd(route('filament.admin.tenant'));
                  // $link = route('filament.admin.tenant') ;
                  // return route('filament.admin.tenant');
                  // return url(url($panel)."/".$record['slug']);
                  return $this->redirect(url($panel)."/".$record['slug'], navigate:false);
                  // return static::getResource()::getUrl('index');
                  // return route('filament.admin.tenant');
      }

      // public static function getRouteName(?string $panel = null): string
      // {
      //     $panel = $panel ? Filament::getPanel($panel) : Filament::getCurrentPanel();
      //     // $routeName = 'pages.' . static::getRelativeRouteName();
      //     $routeName = 'tenant.' . static::getRelativeRouteName(); // here is the change I've made 
      //     $routeName = static::prependClusterRouteBaseName($routeName);
           
      //     return $panel->generateRouteName($routeName);
      // }
}
