<?php

namespace App\Livewire;

use App\Actions\Facturacion\AnulaFactura;
use App\Models\Factura;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Facturas')]
class ListadoFacturas extends Component
{
    use WithPagination;

    public string $filtroEstado     = '';
    public string $filtroFechaDesde = '';
    public string $filtroFechaHasta = '';
    public string $filtroBusqueda   = '';

    // Modal anulación
    public bool   $mostrarModalAnulacion = false;
    public ?int   $facturaAnulandoId     = null;
    public string $facturaAnulandoNum    = '';
    public string $motivoAnulacion       = '';
    public string $errorAnulacion        = '';

    // Modal ver
    public bool    $mostrarModalVer = false;
    public ?Factura $facturaViendo  = null;

    public function updatedFiltroEstado(): void     { $this->resetPage(); }
    public function updatedFiltroFechaDesde(): void { $this->resetPage(); }
    public function updatedFiltroFechaHasta(): void { $this->resetPage(); }
    public function updatedFiltroBusqueda(): void   { $this->resetPage(); }

    // ── Ver ──────────────────────────────────────────────────────────

    public function verFactura(int $id): void
    {
        $this->facturaViendo = Factura::with([
            'detalles',
            'puntoEmision.establecimiento',
            'caiAutorizacion',
        ])->findOrFail($id);

        $this->mostrarModalVer = true;
    }

    public function cerrarVer(): void
    {
        $this->mostrarModalVer = false;
        $this->facturaViendo   = null;
    }

    // ── Anular ───────────────────────────────────────────────────────

    public function abrirAnulacion(int $id, string $numero): void
    {
        $this->facturaAnulandoId     = $id;
        $this->facturaAnulandoNum    = $numero;
        $this->motivoAnulacion       = '';
        $this->errorAnulacion        = '';
        $this->mostrarModalAnulacion = true;
    }

    public function anular(): void
    {
        $this->errorAnulacion = '';

        if (trim($this->motivoAnulacion) === '') {
            $this->errorAnulacion = 'El motivo de anulación es obligatorio.';
            return;
        }

        $factura = Factura::findOrFail($this->facturaAnulandoId);

        try {
            (new AnulaFactura())->anular($factura, $this->motivoAnulacion, auth()->id() ?? 1);
            $this->mostrarModalAnulacion = false;
            $this->dispatch('toast', message: "Factura {$factura->numero_completo} anulada correctamente.", type: 'success');
        } catch (\Exception $e) {
            $this->errorAnulacion = $e->getMessage();
        }
    }

    // ── Render ───────────────────────────────────────────────────────

    public function render(): View
    {
        $query = Factura::with('puntoEmision.establecimiento')
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id');

        if ($this->filtroEstado) {
            $query->where('estado', $this->filtroEstado);
        }
        if ($this->filtroFechaDesde) {
            $query->whereDate('fecha_emision', '>=', $this->filtroFechaDesde);
        }
        if ($this->filtroFechaHasta) {
            $query->whereDate('fecha_emision', '<=', $this->filtroFechaHasta);
        }
        if ($this->filtroBusqueda) {
            $b = $this->filtroBusqueda;
            $query->where(fn ($q) => $q
                ->where('numero_completo', 'like', "%$b%")
                ->orWhere('nombre_cliente', 'like', "%$b%")
                ->orWhere('rtn_cliente', 'like', "%$b%")
            );
        }

        return view('livewire.listado-facturas', [
            'facturas' => $query->paginate(20),
        ]);
    }
}
