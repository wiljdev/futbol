<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Escritorio';

    protected static ?string $title = 'Escritorio';

    protected static string $view = 'filament.pages.dashboard';

    public function getHeading(): string
    {
        return 'Panel de Control';
    }

    public function getSubheading(): ?string
    {
        return 'Bienvenido al sistema de gestión de fútbol';
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
