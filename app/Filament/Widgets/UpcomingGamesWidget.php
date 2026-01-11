<?php

namespace App\Filament\Widgets;

use App\Models\Game;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingGamesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Próximos Partidos')
            ->description('Los siguientes 5 juegos programados')
            ->query(
                Game::query()
                    ->where('date', '>=', now()->toDateString())
                    ->orderBy('date')
                    ->orderBy('time')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('match_number')
                    ->label('Fecha #')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => "Fecha {$state}"),

                Tables\Columns\TextColumn::make('date')
                    ->label('Día')
                    ->date('l, d/m/Y')
                    ->icon('heroicon-o-calendar')
                    ->weight('medium')
                    ->sortable(),

                Tables\Columns\TextColumn::make('time')
                    ->label('Hora')
                    ->time('H:i')
                    ->icon('heroicon-o-clock'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lugar')
                    ->icon('heroicon-o-map-pin')
                    ->limit(30),

                Tables\Columns\TextColumn::make('available_slots')
                    ->label('Cupos')
                    ->badge()
                    ->color(fn ($state) => $state > 5 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state) => "{$state} disponibles"),

                Tables\Columns\TextColumn::make('season_year')
                    ->label('Temporada')
                    ->badge()
                    ->color('gray'),
            ])
            ->paginated(false);
    }
}
