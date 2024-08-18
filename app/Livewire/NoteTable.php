<?php

namespace App\Livewire;

use App\Models\Note;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Widgets\TableWidget as BaseWidget;

class NoteTable extends BaseWidget
{
    public ?Model $record = null;
    public $type ;
    
    public function table(Table $table): Table
    {
        return $table
            ->heading(false)
            ->query(
                Note::query()
                ->where('type_id', $this->record->id)
                ->where('type', $this->type)
                ->where('team_id', Filament::getTenant()->id)
            )
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->model(Note::class)
                    ->form([
                        Textarea::make('content')
                            ->required(),
                        // ...
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['team_id'] = Filament::getTenant()->id;
                        $data['type'] = $this->type;
                        $data['type_id'] = $this->record->id;
                        return $data;
                    }), // Add the custom action button
            ])
            ->columns([
                // ...
                TextColumn::make('user.name'),
                TextColumn::make('content')
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
