<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')
                ->label('Fecha')
                ->required()
                ->default(now()),

            Forms\Components\TimePicker::make('time')
                ->label('Hora')
                ->default('19:00'),

            Forms\Components\TextInput::make('location')
                ->label('Lugar')
                ->default('Cancha habitual'),

            Forms\Components\TextInput::make('season_year')
                ->label('Temporada')
                ->default(now()->year)
                ->readOnly(),

            Forms\Components\TextInput::make('match_number')
                ->label('N° Partido')
                ->readOnly()
                ->helperText('Se calcula automáticamente al crear'),

            Forms\Components\Textarea::make('notes')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('match_number')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('season_year')
                    ->label('Temporada'),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('time')
                    ->label('Hora')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lugar'),

                Tables\Columns\TextColumn::make('available_slots')
                    ->label('Cupos disponibles')
                    ->sortable(),

                Tables\Columns\IconColumn::make('teams_generated')
                    ->boolean()
                    ->label('Equipos listos'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit'   => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
