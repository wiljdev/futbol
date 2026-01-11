<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use App\Models\Game;
use Filament\Resources\Pages\CreateRecord;

class CreateGame extends CreateRecord
{
    protected static string $resource = GameResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // El season_year es el año de la fecha del partido
        $data['season_year'] = \Carbon\Carbon::parse($data['date'])->year;

        // Buscar el último número de partido (del 1 al 13)
        $lastMatchNumber = Game::max('match_number') ?? 0;

        // Si es 13, volver a 1, si no, incrementar
        $data['match_number'] = $lastMatchNumber >= 13 ? 1 : $lastMatchNumber + 1;

        // Valores por defecto si no se envían
        $data['time'] = $data['time'] ?? '19:00:00';
        $data['location'] = $data['location'] ?? 'Cancha habitual';

        return $data;
    }
}
