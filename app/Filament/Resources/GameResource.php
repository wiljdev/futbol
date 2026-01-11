<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
                ->default(now())
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        $set('season_year', Carbon::parse($state)->year);
                    }
                }),

            Forms\Components\TimePicker::make('time')
                ->label('Hora')
                ->default('19:00'),

            Forms\Components\TextInput::make('location')
                ->label('Lugar')
                ->default('Cancha habitual'),

            Forms\Components\TextInput::make('season_year')
                ->label('Temporada (Año)')
                ->default(now()->year)
                ->readOnly()
                ->helperText('Se calcula automáticamente del año de la fecha'),

            Forms\Components\TextInput::make('match_number')
                ->label('N° Partido (1-13)')
                ->numeric()
                ->required()
                ->helperText('Se asigna automáticamente. Usa "Recalcular Números" si insertas partidos'),

            Forms\Components\Textarea::make('notes')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
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
            ->headerActions([
                Tables\Actions\Action::make('recalculate_numbers')
                    ->label('Recalcular Números')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Recalcular números de partidos')
                    ->modalDescription('Esto recalculará los números de TODOS los partidos ordenándolos por fecha. Los números irán del 1 al 13 y luego se reinician. ¿Deseas continuar?')
                    ->action(function () {
                        // Obtener TODOS los juegos ordenados por fecha (ascendente)
                        $games = Game::orderBy('date')->get();

                        // Recalcular números del 1 al 13
                        foreach ($games as $index => $game) {
                            $matchNumber = ($index % 13) + 1;

                            // El season_year es simplemente el año de la fecha del partido
                            $seasonYear = Carbon::parse($game->date)->year;

                            $game->update([
                                'match_number' => $matchNumber,
                                'season_year' => $seasonYear,
                            ]);
                        }

                        Notification::make()
                            ->title('Números recalculados exitosamente')
                            ->success()
                            ->body("Se recalcularon {$games->count()} partidos. Los números van del 1 al 13 y se reinician.")
                            ->send();
                    }),
            ])
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
            'index'       => Pages\ListGames::route('/'),
            'create'      => Pages\CreateGame::route('/create'),
            'bulk-create' => Pages\BulkCreateGames::route('/bulk-create'),
            'edit'        => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
