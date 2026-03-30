<?php

namespace App\Filament\Resources\AttendanceResource\Widgets;

use App\Models\Game;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Obtener el próximo partido
        $nextGame = Game::where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->first();

        // Obtener el partido más reciente
        $lastGame = Game::where('date', '<', now()->toDateString())
            ->orderBy('date', 'desc')
            ->first();

        $stats = [];

        // Estadística del próximo partido
        if ($nextGame) {
            $count = $nextGame->attendances()->count();
            $date = Carbon::parse($nextGame->date)->format('d/m');

            $stats[] = Stat::make("Próximo Partido ({$date})", "{$count}/12")
                ->description($count >= 12 ? '¡Cupo completo!' : (12 - $count) . ' cupos disponibles')
                ->descriptionIcon($count >= 12 ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($count >= 12 ? 'success' : ($count >= 10 ? 'warning' : 'gray'))
                ->chart([$count])
                ->extraAttributes([
                    'class' => $count >= 12 ? 'animate-pulse' : '',
                ]);
        }

        // Estadística del último partido
        if ($lastGame) {
            $lastCount = $lastGame->attendances()->count();
            $lastDate = Carbon::parse($lastGame->date)->format('d/m');

            $stats[] = Stat::make("Último Partido ({$lastDate})", "{$lastCount}/12")
                ->description($lastGame->teams_generated ? 'Equipos generados' : 'Sin equipos')
                ->descriptionIcon($lastGame->teams_generated ? 'heroicon-m-user-group' : 'heroicon-m-question-mark-circle')
                ->color($lastGame->teams_generated ? 'info' : 'gray');
        }

        // Total de asistencias este mes
        $thisMonthCount = Attendance::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $stats[] = Stat::make('Asistencias este mes', $thisMonthCount)
            ->description('Total de inscripciones')
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color('info');

        // Promedio de jugadores por partido (últimos 5 partidos)
        $recentGames = Game::where('date', '<=', now()->toDateString())
            ->orderBy('date', 'desc')
            ->limit(5)
            ->withCount('attendances')
            ->get();

        if ($recentGames->isNotEmpty()) {
            $average = $recentGames->avg('attendances_count');

            $stats[] = Stat::make('Promedio últimos 5 partidos', number_format($average, 1))
                ->description('Jugadores por partido')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($average >= 10 ? 'success' : 'warning');
        }

        return $stats;
    }

    protected function getColumns(): int
    {
        return 2; // Mostrar 2 columnas en desktop, se adaptará en móvil
    }
}
