<?php

namespace App\Livewire;

use App\Actions\Facturacion\EmisorFactura;
use App\Models\CaiAutorizacion;
use App\Models\Cliente;
use App\Models\Impuesto;
use App\Models\Producto;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.pos')]
#[Title('POS — Punto de Venta')]
class PosFactura extends Component
{
    public ?int $puntoEmisionId = null;

    // Búsqueda / barcode
    public string $busqueda   = '';
    public int    $pagina     = 0;
    public int    $porPagina  = 15;

    // Carrito: [producto_id, descripcion, unidad_medida, precio_unitario, cantidad, descuento, impuesto_id, tasa]
    public array $carrito = [];

    // Cliente
    public string $nombreCliente       = 'Consumidor Final';
    public string $rtnCliente          = '';
    public string $direccionCliente    = '';
    public string $clienteSearch       = '';
    public array  $clientesSugeridos   = [];
    public bool   $mostrarClienteModal = false;

    // Pago
    public bool   $mostrarPago = false;
    public string $tipoPago    = 'contado';
    public string $montoPagado = '';

    // Estado
    public ?array $facturaEmitida = null;
    public string $error          = '';
    public ?array $caiActivo      = null;

    #[Locked]
    public array $impuestosIndex = [];

    // Totales derivados
    public float $subtotalExento  = 0;
    public float $subtotalGravado = 0;
    public float $totalIsv        = 0;
    public float $total           = 0;

    public function mount(): void
    {
        $this->puntoEmisionId = session('caja_activa_id');
        $this->impuestosIndex = Impuesto::where('activo', true)
            ->get(['id', 'tasa'])
            ->keyBy('id')
            ->map(fn ($i) => ['tasa' => (float) $i->tasa])
            ->toArray();
        $this->cargarCai();
    }

    #[On('cajaActiva')]
    public function onCajaActiva(int $puntoEmisionId): void
    {
        $this->puntoEmisionId = $puntoEmisionId;
        $this->cargarCai();
    }

    private function cargarCai(): void
    {
        if (! $this->puntoEmisionId) {
            $this->caiActivo = null;
            return;
        }
        $cai = CaiAutorizacion::usable()
            ->delPunto($this->puntoEmisionId, '01')
            ->orderBy('fecha_limite_emision')
            ->first();

        $this->caiActivo = $cai ? [
            'siguiente'    => $cai->correlativo_actual + 1,
            'disponibles'  => $cai->disponibles,
            'por_agotarse' => $cai->por_agotarse,
            'por_vencer'   => $cai->por_vencer,
        ] : null;
    }

    // ── Productos ──────────────────────────────────────────────────

    public function agregarProducto(int $productoId): void
    {
        $producto = Producto::find($productoId);
        if (! $producto) return;

        foreach ($this->carrito as $i => $item) {
            if ($item['producto_id'] === $productoId) {
                $this->carrito[$i]['cantidad']++;
                $this->calcularTotales();
                $this->dispatch('focusBusqueda');
                return;
            }
        }

        $this->carrito[] = [
            'producto_id'     => $producto->id,
            'descripcion'     => $producto->descripcion,
            'unidad_medida'   => $producto->unidad_medida ?? 'unidad',
            'precio_unitario' => (float) $producto->precio_unitario,
            'cantidad'        => 1,
            'descuento'       => 0,
            'impuesto_id'     => $producto->impuesto_id,
            'tasa'            => (float) ($this->impuestosIndex[$producto->impuesto_id]['tasa'] ?? 0),
        ];
        $this->calcularTotales();
        $this->dispatch('focusBusqueda');
    }

    public function buscarBarcode(?string $codigo = null): void
    {
        $codigo = trim($codigo ?? $this->busqueda);
        if (! $codigo) return;

        $producto = Producto::where('activo', true)->where('codigo', $codigo)->first();
        if ($producto) {
            $this->busqueda = '';
            $this->pagina   = 0;
            $this->agregarProducto($producto->id);
        }
        // Si no hay coincidencia exacta por código, el término queda para filtrar la grilla
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'carrito')) {
            $this->calcularTotales();
        }
        if ($property === 'busqueda') {
            $this->pagina = 0;
        }
    }

    public function cambiarCantidad(int $index, int $delta): void
    {
        $nueva = max(0, ($this->carrito[$index]['cantidad'] ?? 1) + $delta);
        if ($nueva === 0) {
            $this->quitarLinea($index);
            return;
        }
        $this->carrito[$index]['cantidad'] = $nueva;
        $this->calcularTotales();
    }

    public function setCantidad(int $index, string $valor): void
    {
        $v = max(1, (int) $valor);
        $this->carrito[$index]['cantidad'] = $v;
        $this->calcularTotales();
    }

    public function quitarLinea(int $index): void
    {
        array_splice($this->carrito, $index, 1);
        $this->calcularTotales();
    }

    public function vaciarCarrito(): void
    {
        $this->carrito = [];
        $this->calcularTotales();
    }

    private function calcularTotales(): void
    {
        $exento = 0; $gravado = 0; $isv = 0;
        foreach ($this->carrito as $item) {
            $tasa     = (float) ($item['tasa'] ?? 0);
            $subtotal = round($item['cantidad'] * $item['precio_unitario'] - ($item['descuento'] ?? 0), 2);
            $isvItem  = round($subtotal * $tasa / 100, 2);
            if ($tasa === 0.0) $exento  += $subtotal;
            else               $gravado += $subtotal;
            $isv += $isvItem;
        }
        $this->subtotalExento  = round($exento, 2);
        $this->subtotalGravado = round($gravado, 2);
        $this->totalIsv        = round($isv, 2);
        $this->total           = round($exento + $gravado + $isv, 2);
    }

    // ── Cliente ────────────────────────────────────────────────────

    public function abrirClienteModal(): void
    {
        $this->clienteSearch     = '';
        $this->clientesSugeridos = [];
        $this->mostrarClienteModal = true;
    }

    public function updatedClienteSearch(): void
    {
        if (strlen($this->clienteSearch) < 2) {
            $this->clientesSugeridos = [];
            return;
        }
        $b = $this->clienteSearch;
        $this->clientesSugeridos = Cliente::where('activo', true)
            ->where(fn ($q) => $q
                ->where('nombre', 'like', "%$b%")
                ->orWhere('rtn', 'like', "%$b%")
            )
            ->limit(8)
            ->get(['id', 'nombre', 'rtn'])
            ->toArray();
    }

    public function seleccionarCliente(int $id): void
    {
        $c = Cliente::findOrFail($id);
        $this->nombreCliente       = $c->nombre;
        $this->rtnCliente          = $c->rtn ?? '';
        $this->direccionCliente    = $c->direccion ?? '';
        $this->mostrarClienteModal = false;
    }

    public function consumidorFinal(): void
    {
        $this->nombreCliente       = 'Consumidor Final';
        $this->rtnCliente          = '';
        $this->direccionCliente    = '';
        $this->mostrarClienteModal = false;
    }

    // ── Pago ───────────────────────────────────────────────────────

    public function abrirPago(): void
    {
        if (empty($this->carrito)) return;
        $this->error       = '';
        $this->montoPagado = number_format($this->total, 2, '.', '');
        $this->mostrarPago = true;
    }

    public function emitir(): void
    {
        $this->error = '';

        if (! $this->puntoEmisionId) {
            $this->error = 'Seleccione una caja antes de emitir.';
            return;
        }
        if (empty($this->carrito)) {
            $this->error = 'El carrito está vacío.';
            return;
        }

        $lineas = array_map(fn ($item) => [
            'descripcion'     => $item['descripcion'],
            'unidad_medida'   => $item['unidad_medida'],
            'cantidad'        => $item['cantidad'],
            'precio_unitario' => $item['precio_unitario'],
            'descuento'       => $item['descuento'] ?? 0,
            'impuesto_id'     => $item['impuesto_id'],
        ], $this->carrito);

        try {
            $factura = (new EmisorFactura())->emitir([
                'punto_emision_id'  => $this->puntoEmisionId,
                'tipo_documento'    => '01',
                'rtn_cliente'       => trim($this->rtnCliente) ?: null,
                'nombre_cliente'    => trim($this->nombreCliente) ?: 'Consumidor Final',
                'direccion_cliente' => trim($this->direccionCliente) ?: null,
                'tipo_pago'         => $this->tipoPago,
                'lineas'            => $lineas,
            ]);

            $this->facturaEmitida = [
                'id'      => $factura->id,
                'numero'  => $factura->numero_completo,
                'total'   => $factura->total,
                'isv'     => $factura->total_isv,
                'gravado' => $factura->subtotal_gravado,
                'exento'  => $factura->subtotal_exento,
                'cliente' => $factura->nombre_cliente,
                'rtn'     => $factura->rtn_cliente,
                'cambio'  => max(0, round((float) $this->montoPagado - $factura->total, 2)),
                'fecha'   => now()->format('d/m/Y H:i'),
            ];

            $this->carrito       = [];
            $this->mostrarPago   = false;
            $this->nombreCliente = 'Consumidor Final';
            $this->rtnCliente    = '';
            $this->tipoPago      = 'contado';
            $this->calcularTotales();
            $this->cargarCai();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error = collect($e->errors())->flatten()->first();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function nuevaVenta(): void
    {
        $this->facturaEmitida = null;
        $this->error          = '';
    }

    public function paginaAnterior(): void
    {
        if ($this->pagina > 0) $this->pagina--;
    }

    public function paginaSiguiente(int $totalProductos): void
    {
        $max = max(0, (int) ceil($totalProductos / $this->porPagina) - 1);
        if ($this->pagina < $max) $this->pagina++;
    }

    public function render(): View
    {
        $q = Producto::where('activo', true);
        if ($this->busqueda) {
            $b = $this->busqueda;
            $q->where(fn ($qr) => $qr
                ->where('descripcion', 'like', "%$b%")
                ->orWhere('codigo', 'like', "%$b%")
            );
        }
        $totalProductos = $q->count();
        $productos = $q->orderBy('descripcion')
            ->skip($this->pagina * $this->porPagina)
            ->take($this->porPagina)
            ->get(['id', 'codigo', 'descripcion', 'precio_unitario', 'impuesto_id', 'unidad_medida', 'imagen']);

        return view('livewire.pos-factura', [
            'productos'      => $productos,
            'totalProductos' => $totalProductos,
            'totalPaginas'   => max(1, (int) ceil($totalProductos / $this->porPagina)),
        ]);
    }
}
