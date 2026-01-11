<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use App\Models\Game;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class BulkCreateGames extends Page
{
    protected static string $resource = GameResource::class;

    protected static string $view = 'filament.resources.game-resource.pages.bulk-create-games';

    protected static ?string $title = 'Crear Juegos Masivamente';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'days_of_week' => ['Wednesday', 'Friday'],
            'time' => '19:00',
            'location' => 'Cancha habitual',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuración de Juegos')
                    ->description('Selecciona el rango de fechas y los días de la semana para crear los juegos automáticamente.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Fecha de Inicio')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now()),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Fecha de Fin')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now()->addMonths(6))
                                    ->after('start_date'),
                            ]),

                        Forms\Components\CheckboxList::make('days_of_week')
                            ->label('Días de la Semana')
                            ->required()
                            ->options([
                                'Monday' => 'Lunes',
                                'Tuesday' => 'Martes',
                                'Wednesday' => 'Miércoles',
                                'Thursday' => 'Jueves',
                                'Friday' => 'Viernes',
                                'Saturday' => 'Sábado',
                                'Sunday' => 'Domingo',
                            ])
                            ->default(['Wednesday', 'Friday'])
                            ->columns(4)
                            ->gridDirection('row')
                            ->helperText('Selecciona los días de la semana en los que se jugarán los partidos.'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('time')
                                    ->label('Hora del Juego')
                                    ->required()
                                    ->default('19:00')
                                    ->seconds(false),

                                Forms\Components\TextInput::make('location')
                                    ->label('Lugar')
                                    ->required()
                                    ->default('Cancha habitual'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas (opcional)')
                            ->rows(3)
                            ->helperText('Estas notas se aplicarán a todos los juegos creados.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Vista Previa')
                    ->description('Se crearán los juegos en las siguientes fechas:')
                    ->schema([
                        Forms\Components\Placeholder::make('preview')
                            ->label('')
                            ->content(function ($get) {
                                $dates = $this->getGameDates($get('start_date'), $get('end_date'), $get('days_of_week') ?? []);

                                if ($dates->isEmpty()) {
                                    return 'No se encontraron fechas con los criterios seleccionados.';
                                }

                                $count = $dates->count();
                                $datesList = $dates->take(10)->map(fn($date) => $date->format('l, d/m/Y'))->join(', ');

                                return "Se crearán **{$count} juegos**.\n\nPrimeras fechas: {$datesList}" . ($count > 10 ? '...' : '');
                            }),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Importante')
                    ->description('Información sobre la numeración de partidos')
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content('**Los números de partido van del 1 al 13 y luego se reinician.**

Después de crear los juegos masivamente, usa el botón **"Recalcular Números"** en la lista de juegos para reorganizar todos los partidos correctamente por fecha.

Esto es útil si:
- Insertas un partido en medio de otros
- Se cancela o elimina un partido
- Quieres reordenar toda la lista'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    protected function getGameDates(?string $startDate, ?string $endDate, array $daysOfWeek): Collection
    {
        if (!$startDate || !$endDate || empty($daysOfWeek)) {
            return collect();
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $dates = collect();

        $current = $start->copy();

        while ($current->lte($end)) {
            if (in_array($current->format('l'), $daysOfWeek)) {
                $dates->push($current->copy());
            }
            $current->addDay();
        }

        return $dates;
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $dates = $this->getGameDates(
            $data['start_date'],
            $data['end_date'],
            $data['days_of_week']
        );

        if ($dates->isEmpty()) {
            Notification::make()
                ->title('No se pueden crear juegos')
                ->body('No se encontraron fechas válidas con los criterios seleccionados.')
                ->warning()
                ->send();

            return;
        }

        $lastMatchNumber = Game::max('match_number') ?? 0;

        $created = 0;

        foreach ($dates as $date) {
            // Verificar si ya existe un juego en esa fecha
            $exists = Game::where('date', $date->format('Y-m-d'))->exists();

            if (!$exists) {
                // Incrementar número (del 1 al 13, luego reinicia)
                $lastMatchNumber++;
                if ($lastMatchNumber > 13) {
                    $lastMatchNumber = 1;
                }

                Game::create([
                    'date' => $date->format('Y-m-d'),
                    'time' => $data['time'],
                    'location' => $data['location'],
                    'notes' => $data['notes'] ?? null,
                    'match_number' => $lastMatchNumber,
                    'season_year' => $date->year, // Año de la fecha del partido
                ]);

                $created++;
            }
        }

        Notification::make()
            ->title('Juegos creados exitosamente')
            ->body("Se crearon {$created} juegos de {$dates->count()} fechas seleccionadas.")
            ->success()
            ->send();

        $this->redirect(GameResource::getUrl('index'));
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('create')
                ->label('Crear Juegos')
                ->icon('heroicon-o-plus-circle')
                ->action('create')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmar creación masiva')
                ->modalDescription('¿Estás seguro de que deseas crear estos juegos? Se omitirán las fechas que ya tengan juegos programados.')
                ->modalSubmitActionLabel('Sí, crear juegos'),
        ];
    }
}
