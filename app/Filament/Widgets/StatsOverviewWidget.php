<?php

namespace App\Filament\Widgets;

use App\Models\Game;
use App\Models\Player;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalGames = Game::count();
        $totalPlayers = Player::count();

        $nextGame = Game::where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->first();

        $nextGameDate = $nextGame
            ? $nextGame->date->format('d/m/Y') . ' a las ' . date('H:i', strtotime($nextGame->time))
            : 'Sin partidos programados';

        $gamesThisMonth = Game::whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->count();

        return [
            Stat::make('Total de Juegos', $totalGames)
                ->description('Partidos registrados en el sistema')
                ->descriptionIcon('heroicon-o-trophy')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Total de Jugadores', $totalPlayers)
                ->description('Jugadores registrados')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('warning')
                ->chart([1, 2, 3, 4, 5, 6, 7, 8]),

            Stat::make('Próximo Partido', $nextGame ? "Fecha {$nextGame->match_number}" : 'N/A')
                ->description($nextGameDate)
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Partidos Este Mes', $gamesThisMonth)
                ->description('Juegos programados en ' . now()->locale('es')->monthName)
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),
        ];
    }
}
