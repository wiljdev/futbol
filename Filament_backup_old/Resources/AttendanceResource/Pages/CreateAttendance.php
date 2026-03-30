<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Models\Player;
use App\Models\Game;
use App\Models\Attendance;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Asistencia registrada correctamente';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si no se seleccionó un jugador existente, crear uno nuevo
        if (!$data['player_id'] && $data['name']) {
            $player = Player::firstOrCreate(['name' => trim($data['name'])]);
            $data['player_id'] = $player->id;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Verificar que no exista duplicado
        $exists = Attendance::where('game_id', $data['game_id'])
            ->where('name', 'like', '%' . trim($data['name']) . '%')
            ->exists();

        if ($exists) {
            Notification::make()
                ->warning()
                ->title('⚠️ Jugador ya inscrito')
                ->body('Este jugador ya está inscrito para este partido.')
                ->send();

            $this->halt();
        }

        // Verificar cupo disponible
        $game = Game::withCount('attendances')->find($data['game_id']);
        $currentCount = $game->attendances_count ?? 0;

        if ($currentCount >= 12) {
            Notification::make()
                ->danger()
                ->title('⛔ Cupo lleno')
                ->body('El cupo ya está lleno (12/12 jugadores).')
                ->send();

            $this->halt();
        }

        // Crear la asistencia
        $attendance = parent::handleRecordCreation($data);

        // Refrescar el juego para obtener el nuevo conteo
        $game->loadCount('attendances');
        $newCount = $game->attendances_count;

        // Generar equipos automáticamente si llegamos a 10 jugadores
        if ($newCount >= 10 && !$game->teams_generated) {
            $game->generateRandomTeams();

            Notification::make()
                ->success()
                ->title('⚽ Equipos generados')
                ->body('¡Equipos generados automáticamente!')
                ->send();
        }

        // Notificación personalizada según posición
        $position = $newCount;
        $status = $position <= 10 ? 'Titular' : 'Suplente';

        // Sobrescribir la notificación por defecto
        $this->getCreatedNotification()?->send();

        Notification::make()
            ->success()
            ->title('✅ Jugador inscrito')
            ->body("Inscrito como #{$position} ({$status})")
            ->send();

        return $attendance;
    }

    protected function getCreatedNotification(): ?Notification
    {
        // Retornamos null para manejar las notificaciones manualmente
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Registrar Asistencia'),
            $this->getCancelFormAction(),
        ];
    }

    public function getTitle(): string
    {
        return 'Nueva Asistencia';
    }

    public function getSubheading(): ?string
    {
        return 'Registra un nuevo jugador para un partido específico.';
    }
}
