<x-filament-panels::page>
    {{-- Mensaje de bienvenida --}}
    <div class="mb-6 rounded-lg bg-gradient-to-r from-amber-500 to-orange-600 p-6 text-white shadow-lg">
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
    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns()"
    />
</x-filament-panels::page>
