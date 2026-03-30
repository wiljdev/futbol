<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Models\Game;
use App\Models\Attendance;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Carbon\Carbon;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Asistencia')
                ->icon('heroicon-o-plus')
                ->iconPosition(IconPosition::Before),

            Actions\Action::make('generate_teams')
                ->label('Generar Equipos')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->action(function () {
                    $nextGame = Game::where('date', '>=', now()->toDateString())
                        ->orderBy('date')
                        ->withCount('attendances')
                        ->first();

                    if ($nextGame && $nextGame->attendances_count >= 10) {
                        $success = $nextGame->generateRandomTeams();

                        if ($success) {
                            $this->notify('success', '¡Equipos generados correctamente!');
                        } else {
                            $this->notify('warning', 'Se necesitan al menos 10 jugadores para generar equipos.');
                        }
                    } else {
                        $this->notify('warning', 'No hay suficientes jugadores para generar equipos.');
                    }
                })
                ->visible(function () {
                    $nextGame = Game::where('date', '>=', now()->toDateString())
                        ->orderBy('date')
                        ->withCount('attendances')
                        ->first();

                    return $nextGame && $nextGame->attendances_count >= 10;
                })
                ->requiresConfirmation()
                ->modalHeading('Generar Equipos Aleatorios')
                ->modalDescription('Esto generará dos equipos aleatorios con los primeros 10 jugadores inscritos. ¿Continuar?'),

            Actions\Action::make('view_teams')
                ->label('Ver Equipos')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading('⚽ Equipos Generados')
                ->modalContent(function () {
                    $nextGame = Game::where('date', '>=', now()->toDateString())
                        ->orderBy('date')
                        ->first();

                    if (!$nextGame || !$nextGame->teams_generated) {
                        return view('filament.modal-content', [
                            'content' => '<p>No hay equipos generados para mostrar.</p>'
                        ]);
                    }

                    $teamA = $nextGame->team_a ?? [];
                    $teamB = $nextGame->team_b ?? [];
                    $date = Carbon::parse($nextGame->date)->format('d/m/Y');

                    $html = "
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <h3>Fecha {$nextGame->match_number} - {$date}</h3>
                        </div>

                        <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;'>
                            <div style='background: #fee; padding: 15px; border-radius: 8px; border: 2px solid #f44336;'>
                                <h4 style='color: #f44336; text-align: center; margin-bottom: 10px;'>🔴 Equipo A</h4>
                                <ol style='margin: 0; padding-left: 20px;'>";

                    foreach ($teamA as $player) {
                        $html .= "<li style='margin: 5px 0;'>{$player}</li>";
                    }

                    $html .= "
                                </ol>
                            </div>

                            <div style='background: #e3f2fd; padding: 15px; border-radius: 8px; border: 2px solid #2196f3;'>
                                <h4 style='color: #2196f3; text-align: center; margin-bottom: 10px;'>🔵 Equipo B</h4>
                                <ol style='margin: 0; padding-left: 20px;'>";

                    foreach ($teamB as $player) {
                        $html .= "<li style='margin: 5px 0;'>{$player}</li>";
                    }

                    $html .= "
                                </ol>
                            </div>
                        </div>";

                    // Mostrar suplentes si los hay
                    $allAttendees = $nextGame->attendances()->orderBy('created_at')->pluck('name')->toArray();
                    $substitutes = array_slice($allAttendees, 10);

                    if (!empty($substitutes)) {
                        $html .= "
                            <div style='background: #fff3e0; padding: 15px; border-radius: 8px; border: 2px solid #ff9800; text-align: center;'>
                                <h4 style='color: #ff9800; margin-bottom: 10px;'>🔄 Suplentes</h4>
                                <p style='margin: 0;'>" . implode(', ', $substitutes) . "</p>
                            </div>";
                    }

                    return new \Illuminate\Support\HtmlString($html);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->visible(function () {
                    $nextGame = Game::where('date', '>=', now()->toDateString())
                        ->orderBy('date')
                        ->first();

                    return $nextGame && $nextGame->teams_generated;
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceResource\Widgets\AttendanceStatsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        $nextGame = Game::where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->withCount('attendances')
            ->first();

        if ($nextGame) {
            $date = Carbon::parse($nextGame->date)->format('d/m/Y');
            $count = $nextGame->attendances_count ?? 0;
            return "Asistencias - Próximo partido: {$date} ({$count}/12)";
        }

        return 'Asistencias';
    }

    public function getSubheading(): ?string
    {
        $nextGame = Game::where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->withCount('attendances')
            ->first();

        if ($nextGame) {
            $count = $nextGame->attendances_count ?? 0;

            if ($count >= 12) {
                return '🎉 ¡Cupo completo! Ya están listos los 12 jugadores.';
            } elseif ($count >= 10) {
                return '⚽ Listos para generar equipos. ' . (12 - $count) . ' cupo(s) disponible(s).';
            } elseif ($count > 0) {
                return '📝 Faltan ' . (10 - $count) . ' jugador(es) para completar titulares.';
            } else {
                return '🤔 Aún no hay jugadores inscritos para el próximo partido.';
            }
        }

        return 'Administra las asistencias a los partidos de fútbol.';
    }
}
