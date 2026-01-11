<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGames extends ListRecords
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Juego'),
            Actions\Action::make('bulk_create')
                ->label('Crear Juegos Masivamente')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->url(fn (): string => GameResource::getUrl('bulk-create')),
        ];
    }
}
