<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\Game;
use App\Models\Player;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\FiltersLayout;
use Carbon\Carbon;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Asistencias';

    protected static ?string $modelLabel = 'Asistencia';

    protected static ?string $pluralModelLabel = 'Asistencias';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('game_id')
                    ->label('Partido')
                    ->options(function () {
                        return Game::query()
                            ->orderByDesc('date')
                            ->get()
                            ->mapWithKeys(function ($game) {
                                $date = Carbon::parse($game->date)->format('d/m/Y');
                                $label = "Fecha {$game->match_number} - {$date}";
                                if ($game->season_year) {
                                    $label .= " (Temporada {$game->season_year})";
                                }
                                return [$game->id => $label];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        // Limpiar el campo de jugador cuando cambie el partido
                        $set('player_id', null);
                        $set('name', '');
                    }),

                Forms\Components\Select::make('player_id')
                    ->label('Jugador Recurrente')
                    ->options(Player::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $get, $state) {
                        if ($state) {
                            $player = Player::find($state);
                            $set('name', $player?->name ?? '');
                        }
                    })
                    ->helperText('Selecciona un jugador existente o deja vacío para crear uno nuevo'),

                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Jugador')
                    ->required()
                    ->maxLength(255)
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $get, $state) {
                        // Si escribe un nombre, buscar si ya existe como jugador
                        if ($state && !$get('player_id')) {
                            $player = Player::where('name', 'like', "%{$state}%")->first();
                            if ($player) {
                                $set('player_id', $player->id);
                            }
                        }
                    })
                    ->helperText('Nombre completo del jugador'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->with(['game', 'player'])
                    ->join('games', 'attendances.game_id', '=', 'games.id')
                    ->select('attendances.*', 'games.date as game_date', 'games.match_number');
            })
            ->columns([
                Tables\Columns\TextColumn::make('match_number')
                    ->label('Fecha')
                    ->formatStateUsing(function ($record) {
                        $date = Carbon::parse($record->game_date)->format('d/m/Y');
                        return "Fecha {$record->match_number} - {$date}";
                    })
                    ->sortable(['games.date'])
                    ->searchable(['games.match_number'])
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('name')
                    ->label('Jugador')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('position')
                    ->label('Posición')
                    ->getStateUsing(function ($record) {
                        // Calcular la posición del jugador en el partido
                        $position = Attendance::where('game_id', $record->game_id)
                            ->where('created_at', '<=', $record->created_at)
                            ->count();

                        return $position;
                    })
                    ->badge()
                    ->color(fn ($state) => $state <= 10 ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => "#{$state} " . ($state <= 10 ? 'Titular' : 'Suplente')),

                Tables\Columns\TextColumn::make('player.name')
                    ->label('Jugador Recurrente')
                    ->placeholder('Jugador ocasional')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrito el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_attendances')
                    ->label('Total Inscritos')
                    ->getStateUsing(function ($record) {
                        return $record->game->attendances()->count();
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state >= 12) return 'danger';
                        if ($state >= 10) return 'success';
                        return 'gray';
                    })
                    ->formatStateUsing(fn ($state) => "{$state}/12"),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('game_id')
                    ->label('Partido')
                    ->options(function () {
                        return Game::query()
                            ->orderByDesc('date')
                            ->limit(10) // Solo los últimos 10 partidos
                            ->get()
                            ->mapWithKeys(function ($game) {
                                $date = Carbon::parse($game->date)->format('d/m/Y');
                                return [$game->id => "Fecha {$game->match_number} - {$date}"];
                            });
                    })
                    ->preload(),

                Tables\Filters\Filter::make('only_starters')
                    ->label('Solo Titulares')
                    ->query(function (Builder $query) {
                        return $query->whereRaw('(
                            SELECT COUNT(*)
                            FROM attendances a2
                            WHERE a2.game_id = attendances.game_id
                            AND a2.created_at <= attendances.created_at
                        ) <= 10');
                    }),

                Tables\Filters\Filter::make('only_substitutes')
                    ->label('Solo Suplentes')
                    ->query(function (Builder $query) {
                        return $query->whereRaw('(
                            SELECT COUNT(*)
                            FROM attendances a2
                            WHERE a2.game_id = attendances.game_id
                            AND a2.created_at <= attendances.created_at
                        ) > 10');
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar Asistencia')
                    ->successNotificationTitle('Asistencia actualizada correctamente'),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Eliminar Asistencia')
                    ->modalDescription('¿Estás seguro de que quieres eliminar esta asistencia? Esta acción no se puede deshacer.')
                    ->successNotificationTitle('Asistencia eliminada correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Eliminar Asistencias Seleccionadas')
                        ->modalDescription('¿Estás seguro de que quieres eliminar las asistencias seleccionadas? Esta acción no se puede deshacer.')
                        ->successNotificationTitle('Asistencias eliminadas correctamente'),
                ]),
            ])
            ->defaultSort('games.date', 'desc')
            ->recordUrl(null) // Deshabilitar clic en filas
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('30s'); // Auto-refresh cada 30 segundos
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Mostrar el número de asistencias del próximo partido
        $nextGame = Game::where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->withCount('attendances')
            ->first();

        if ($nextGame) {
            $count = $nextGame->attendances_count ?? 0;
            return $count > 0 ? "{$count}/12" : null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $nextGame = Game::where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->withCount('attendances')
            ->first();

        if ($nextGame) {
            $count = $nextGame->attendances_count ?? 0;
            if ($count >= 12) return 'danger';
            if ($count >= 10) return 'success';
            if ($count > 0) return 'warning';
        }

        return 'gray';
    }
}
