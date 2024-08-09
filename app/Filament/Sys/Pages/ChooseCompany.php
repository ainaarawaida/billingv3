<?php

namespace App\Filament\Sys\Pages;

use App\Models\Team;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Tables\Actions\Action;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Sys\Pages\Tenancy\RegisterTeam;
use Filament\Tables\Concerns\InteractsWithTable;

class ChooseCompany extends Page implements HasForms, HasTable
{

    use InteractsWithTable;
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $layout = 'filament-panels::components.layout.index';
    protected static string $view = 'filament.sys.pages.choose-company';
    protected static bool $shouldRegisterNavigation = false;

    public function getTableQueryForExport(): Builder
    {
        return Team::query()->whereHas('members', function($q) {
            $q->where('users.id', auth()->user()->id);
        }); // Or customize the query as needed
    }
    
    public function table(Table $table): Table
    {
      
        return $table
            ->query(Team::query()->whereHas('members', function($q) {
                $q->where('users.id', auth()->user()->id);
            }))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('phone'),
                ImageColumn::make('photo')
            ])
            ->filters([
              
            ])
            ->actions([
                Action::make('select')
                ->label('Select')
                ->url(function($record) {
                        return url(Filament::getCurrentPanel()->getPath().'/'.$record->slug);
                    }
                ),
            ])
            ->bulkActions([
                // ...
            ])
            ->recordUrl(
                fn (Model $record): string => url(Filament::getCurrentPanel()->getPath().'/'.$record->slug)
            );
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('Create Company')
            ->visible(RegisterTeam::canview())
            ->url(function() {
                    return url(Filament::getCurrentPanel()->getPath().'/new');
                }
            ),
          
        ];
    }
   
}
