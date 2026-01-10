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
                ->numeric()
                ->required()
                ->helperText('Se calcula automáticamente al crear, pero puedes editarlo manualmente si es necesario'),

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
            ->headerActions([
                Tables\Actions\Action::make('recalculate_numbers')
                    ->label('Recalcular Números')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Recalcular números de partido')
                    ->modalDescription('Esto recalculará los números de todos los partidos de la temporada actual ordenándolos por fecha. ¿Deseas continuar?')
                    ->action(function () {
                        $season = now()->year;

                        // Obtener todos los juegos de la temporada actual ordenados por fecha
                        $games = Game::where('season_year', $season)
                            ->orderBy('date')
                            ->get();

                        // Asignar números secuenciales
                        foreach ($games as $index => $game) {
                            $game->update(['match_number' => $index + 1]);
                        }

                        Notification::make()
                            ->title('Números recalculados')
                            ->success()
                            ->body("Se recalcularon {$games->count()} partidos de la temporada {$season}.")
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
            'index'  => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit'   => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
