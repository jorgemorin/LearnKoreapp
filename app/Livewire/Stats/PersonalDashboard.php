<?php

namespace App\Livewire\Stats;

use App\Services\StatsService;
use Livewire\Component;

/**
 * Dashboard de estadísticas personales del usuario.
 * Muestra métricas SRS: acierto global, por tag, por tipo morfológico y sesiones recientes.
 */
class PersonalDashboard extends Component
{
    public array $stats     = [];
    public bool  $loading   = true;
    public string $error    = '';

    public function mount(StatsService $statsService): void
    {
        try {
            $this->stats   = $statsService->getPersonalStats(auth()->id());
            $this->loading = false;
        } catch (\Throwable $e) {
            $this->error   = 'Error al cargar estadísticas: ' . $e->getMessage();
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.stats.personal-dashboard');
    }
}
