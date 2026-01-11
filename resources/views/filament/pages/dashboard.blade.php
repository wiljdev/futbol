<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Mensaje de bienvenida --}}
        <div class="rounded-lg bg-gradient-to-r from-amber-500 to-orange-600 p-6 text-white shadow-lg">
            <div class="flex items-center gap-4">
                <div class="rounded-full bg-white/20 p-3">
                    <x-heroicon-o-trophy class="h-8 w-8" />
                </div>
                <div>
                    <h2 class="text-2xl font-bold">¡Bienvenido a FutbolApp!</h2>
                    <p class="text-amber-50">Gestiona tus partidos y jugadores de forma fácil y rápida</p>
                </div>
            </div>
        </div>

        {{-- Widgets de estadísticas y tabla --}}
        @livewire(\Filament\Widgets\WidgetsServiceProvider::class)
    </div>
</x-filament-panels::page>
