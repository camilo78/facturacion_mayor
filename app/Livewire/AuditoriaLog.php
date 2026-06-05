<?php

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
#[Title('Auditoría')]
class AuditoriaLog extends Component
{
    use WithPagination;

    public string $filtroFechaDesde = '';
    public string $filtroFechaHasta = '';
    public string $filtroUsuario    = '';
    public string $filtroEvento     = '';

    public function updatedFiltroFechaDesde(): void { $this->resetPage(); }
    public function updatedFiltroFechaHasta(): void { $this->resetPage(); }
    public function updatedFiltroUsuario(): void    { $this->resetPage(); }
    public function updatedFiltroEvento(): void     { $this->resetPage(); }

    public function render(): View
    {
        $query = Activity::with('causer')
            ->latest();

        if ($this->filtroFechaDesde) {
            $query->whereDate('created_at', '>=', $this->filtroFechaDesde);
        }
        if ($this->filtroFechaHasta) {
            $query->whereDate('created_at', '<=', $this->filtroFechaHasta);
        }
        if ($this->filtroUsuario) {
            $query->whereHasMorph(
                'causer',
                \App\Models\User::class,
                fn ($q) => $q->where('name', 'like', "%{$this->filtroUsuario}%")
                             ->orWhere('email', 'like', "%{$this->filtroUsuario}%")
            );
        }
        if ($this->filtroEvento) {
            $query->where('event', 'like', "%{$this->filtroEvento}%")
                  ->orWhere('description', 'like', "%{$this->filtroEvento}%");
        }

        return view('livewire.auditoria-log', [
            'actividades' => $query->paginate(30),
        ]);
    }
}
