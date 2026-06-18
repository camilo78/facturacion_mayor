<?php

namespace App\Livewire;

use App\Models\Instance;
use App\Models\SyncQueue;
use Illuminate\View\View;
use Livewire\Component;

class MonitoreoInstancias extends Component
{
    public function mount(): void
    {
        abort_unless(config('instance.is_mayor'), 403, 'Solo disponible en el nodo Mayor.');
    }

    public function toggleActivo(string $instanceId): void
    {
        $instance = Instance::findOrFail($instanceId);
        $instance->update(['activo' => ! $instance->activo]);
    }

    public function render(): View
    {
        $instancias = Instance::with('tenant')
            ->where('tipo', 'auxiliar')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn ($inst) => [
                'id'            => $inst->id,
                'label'         => $inst->label,
                'tenant_id'     => $inst->tenant_id,
                'tenant_nombre' => $inst->tenant?->nombre ?? '—',
                'activo'        => $inst->activo,
                'last_seen_at'  => $inst->last_seen_at,
                'estado'        => $this->estadoConexion($inst),
                'pendientes'    => $this->contarPendientes($inst),
            ]);

        $resumen = [
            'total'         => $instancias->count(),
            'conectadas'    => $instancias->where('estado', 'conectado')->count(),
            'inactivas'     => $instancias->where('estado', 'inactivo')->count(),
            'desconectadas' => $instancias->where('estado', 'desconectado')->count(),
        ];

        return view('livewire.monitoreo-instancias', compact('instancias', 'resumen'))
            ->layout('layouts.mayor', ['title' => 'Monitoreo de Instancias']);
    }

    private function estadoConexion(Instance $instance): string
    {
        if (! $instance->last_seen_at) {
            return 'desconectado';
        }

        $minutos = $instance->last_seen_at->diffInMinutes(now());

        return match (true) {
            $minutos <= 15  => 'conectado',
            $minutos <= 60  => 'inactivo',
            default         => 'desconectado',
        };
    }

    private function contarPendientes(Instance $instance): int
    {
        if (! $instance->tenant_id) {
            return 0;
        }

        try {
            tenancy()->initialize($instance->tenant_id);
            $count = SyncQueue::pendiente()->count();
            tenancy()->end();
            return $count;
        } catch (\Throwable) {
            try {
                tenancy()->end();
            } catch (\Throwable) {
            }
            return -1;
        }
    }
}
