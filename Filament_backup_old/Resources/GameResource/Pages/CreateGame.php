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
        // Temporada actual
        $data['season_year'] = now()->year;

        // Buscar último número de partido de la temporada
        $lastMatchNumber = Game::where('season_year', $data['season_year'])
            ->max('match_number');

        $data['match_number'] = $lastMatchNumber ? $lastMatchNumber + 1 : 1;

        // Valores por defecto si no se envían
        $data['time'] = $data['time'] ?? '19:00:00';
        $data['location'] = $data['location'] ?? 'Cancha habitual';

        return $data;
    }
}
