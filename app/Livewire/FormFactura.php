<?php

namespace App\Livewire;

use App\Actions\Facturacion\EmisorFactura;
use App\Models\CaiAutorizacion;
use App\Models\Cliente;
use App\Models\Impuesto;
use App\Models\Producto;
use App\Models\PuntoEmision;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Nueva factura')]
class FormFactura extends Component
{
    // Cabecera
    public ?int   $puntoEmisionId    = null;
    public string $tipoDocumento     = '01';
    public string $rtnCliente        = '';
    public string $nombreCliente     = 'Consumidor Final';
    public string $direccionCliente       = '';
    public string $tipoPago               = 'contado';
    public string $ordenCompraExenta      = '';
    public string $numConstanciaExonerado = '';
    public string $numRegistroSag         = '';

    // Autocomplete cliente
    public string $clienteSearch        = '';
    public array  $clientesSugeridos    = [];
    public bool   $mostrarClienteDropdown = false;

    // Líneas
    public array $lineas = [];

    // Totales
    public float $subtotalExento  = 0;
    public float $subtotalGravado = 0;
    public float $totalIsv        = 0;
    public float $total           = 0;

    // CAI activo
    public ?array $caiActivo = null;

    // Post-emisión
    public ?array $facturaEmitida = null;
    public string $error          = '';

    #[Locked]
    public array $impuestosIndex = [];

    public function mount(): void
    {
        // Inicializar desde caja activa de sesión
        $this->puntoEmisionId = session('caja_activa_id');

        $this->impuestosIndex = Impuesto::where('activo', true)
            ->get(['id', 'tasa'])
            ->keyBy('id')
            ->map(fn ($i) => ['tasa' => (float) $i->tasa])
            ->toArray();

        $this->agregarLinea();
        $this->cargarCai();
    }

    #[On('cajaActiva')]
    public function onCajaActiva(int $puntoEmisionId): void
    {
        $this->puntoEmisionId = $puntoEmisionId;
        $this->cargarCai();
    }

    public function updatedPuntoEmisionId(): void
    {
        $this->cargarCai();
    }

    private function cargarCai(): void
    {
        if (! $this->puntoEmisionId) {
            $this->caiActivo = null;
            return;
        }

        $cai = CaiAutorizacion::usable()
            ->delPunto($this->puntoEmisionId, $this->tipoDocumento)
            ->orderBy('fecha_limite_emision')
            ->first();

        $this->caiActivo = $cai ? [
            'cai'                => $cai->cai,
            'correlativo_actual' => $cai->correlativo_actual,
            'siguiente'          => $cai->correlativo_actual + 1,
            'rango_final'        => $cai->rango_final,
            'disponibles'        => $cai->disponibles,
            'porcentaje'         => round($cai->porcentaje_usado * 100),
            'fecha_limite'       => $cai->fecha_limite_emision->format('d/m/Y'),
            'dias_restantes'     => $cai->fecha_limite_emision->diffInDays(today()),
            'por_agotarse'       => $cai->por_agotarse,
            'por_vencer'         => $cai->por_vencer,
        ] : null;
    }

    // ── Autocomplete cliente ────────────────────────────

    public function updatedClienteSearch(): void
    {
        if (strlen($this->clienteSearch) < 2) {
            $this->clientesSugeridos = [];
            $this->mostrarClienteDropdown = false;
            return;
        }
        $busq = $this->clienteSearch;
        $this->clientesSugeridos = Cliente::where('activo', true)
            ->where(fn ($q) => $q
                ->where('nombre', 'like', "%$busq%")
                ->orWhere('rtn', 'like', "%$busq%")
            )
            ->limit(6)
            ->get(['id', 'nombre', 'rtn'])
            ->toArray();
        $this->mostrarClienteDropdown = ! empty($this->clientesSugeridos);
    }

    public function seleccionarCliente(int $id): void
    {
        $cliente = Cliente::findOrFail($id);
        $this->rtnCliente       = $cliente->rtn ?? '';
        $this->nombreCliente    = $cliente->nombre;
        $this->direccionCliente = $cliente->direccion ?? '';
        $this->clienteSearch    = $cliente->nombre;
        $this->mostrarClienteDropdown = false;
        $this->clientesSugeridos = [];
    }

    public function consumidorFinal(): void
    {
        $this->rtnCliente       = '';
        $this->nombreCliente    = 'Consumidor Final';
        $this->direccionCliente = '';
        $this->clienteSearch    = '';
        $this->mostrarClienteDropdown = false;
    }

    // ── Líneas de detalle ────────────────────────────────

    public function agregarLinea(): void
    {
        $defaultId = Impuesto::where('es_default', true)->where('activo', true)->value('id');
        $this->lineas[] = [
            'producto_id'     => '',
            'descripcion'     => '',
            'cantidad'        => 1,
            'unidad_medida'   => 'unidad',
            'precio_unitario' => '',
            'descuento'       => 0,
            'impuesto_id'     => $defaultId ?? '',
        ];
    }

    public function quitarLinea(int $index): void
    {
        array_splice($this->lineas, $index, 1);
        $this->calcularTotales();
    }

    public function seleccionarProducto(int $index, mixed $productoId): void
    {
        if (! $productoId) return;
        $producto = Producto::find((int) $productoId);
        if (! $producto) return;

        $this->lineas[$index]['descripcion']   = $producto->descripcion;
        $this->lineas[$index]['unidad_medida'] = $producto->unidad_medida ?? 'unidad';
        $this->lineas[$index]['impuesto_id']   = $producto->impuesto_id;
        $this->lineas[$index]['producto_id']   = $producto->id;

        if (! $producto->precio_editable_en_emision || empty($this->lineas[$index]['precio_unitario'])) {
            $this->lineas[$index]['precio_unitario'] = $producto->precio_unitario;
        }
        $this->calcularTotales();
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'lineas')) {
            $this->calcularTotales();
        }
    }

    private function calcularTotales(): void
    {
        $exento = 0; $gravado = 0; $isv = 0;

        foreach ($this->lineas as $linea) {
            $impId  = $linea['impuesto_id'] ?? null;
            $precio = (float) ($linea['precio_unitario'] ?? 0);
            if (! $impId || $precio <= 0) continue;

            $tasa     = (float) ($this->impuestosIndex[(string) $impId]['tasa'] ?? 0);
            $cant     = max(0.001, (float) ($linea['cantidad'] ?? 1));
            $desc     = max(0, (float) ($linea['descuento'] ?? 0));
            $subtotal = round($cant * $precio - $desc, 2);
            $isvLinea = round($subtotal * $tasa / 100, 2);

            if ($tasa === 0.0) $exento += $subtotal;
            else               $gravado += $subtotal;
            $isv += $isvLinea;
        }

        $this->subtotalExento  = round($exento, 2);
        $this->subtotalGravado = round($gravado, 2);
        $this->totalIsv        = round($isv, 2);
        $this->total           = round($exento + $gravado + $isv, 2);
    }

    // ── Emisión ──────────────────────────────────────────

    public function emitir(): void
    {
        $this->error = '';

        if (! $this->puntoEmisionId) {
            $this->error = 'Seleccione una caja antes de emitir.';
            return;
        }

        $lineasValidas = array_values(
            array_filter($this->lineas, fn ($l) => ! empty($l['precio_unitario']) && (float) $l['precio_unitario'] > 0)
        );

        if (empty($lineasValidas)) {
            $this->error = 'Agregue al menos una línea con precio válido.';
            return;
        }

        try {
            $factura = (new EmisorFactura())->emitir([
                'punto_emision_id' => $this->puntoEmisionId,
                'tipo_documento'   => $this->tipoDocumento,
                'rtn_cliente'              => trim($this->rtnCliente) ?: null,
                'nombre_cliente'           => trim($this->nombreCliente) ?: 'Consumidor Final',
                'direccion_cliente'        => trim($this->direccionCliente) ?: null,
                'tipo_pago'                => $this->tipoPago,
                'orden_compra_exenta'      => trim($this->ordenCompraExenta) ?: null,
                'num_constancia_exonerado' => trim($this->numConstanciaExonerado) ?: null,
                'num_registro_sag'         => trim($this->numRegistroSag) ?: null,
                'lineas'           => $lineasValidas,
            ]);

            $this->facturaEmitida = [
                'numero'    => $factura->numero_completo,
                'total'     => $factura->total,
                'exento'    => $factura->subtotal_exento,
                'gravado'   => $factura->subtotal_gravado,
                'isv'       => $factura->total_isv,
                'cai'       => $factura->cai,
                'cliente'   => $factura->nombre_cliente,
                'tipo_pago' => $factura->tipo_pago,
                'fecha'     => now()->format('d/m/Y H:i'),
            ];

            $savedPunto = $this->puntoEmisionId;
            $this->reset(['rtnCliente', 'direccionCliente', 'lineas', 'subtotalExento', 'subtotalGravado',
                          'totalIsv', 'total', 'error', 'clienteSearch', 'clientesSugeridos',
                          'ordenCompraExenta', 'numConstanciaExonerado', 'numRegistroSag']);
            $this->nombreCliente  = 'Consumidor Final';
            $this->tipoPago       = 'contado';
            $this->puntoEmisionId = $savedPunto;
            $this->agregarLinea();
            $this->cargarCai();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error = collect($e->errors())->flatten()->first();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function nuevaFactura(): void
    {
        $this->facturaEmitida = null;
    }

    public function render(): View
    {
        return view('livewire.form-factura', [
            'puntosEmision' => PuntoEmision::with('establecimiento')
                ->where('activo', true)
                ->orderBy('establecimiento_id')->orderBy('codigo')->get(),
            'impuestos' => Impuesto::where('activo', true)->orderBy('tasa')->get(),
            'productos'  => Producto::where('activo', true)->orderBy('descripcion')
                ->get(['id', 'codigo', 'descripcion', 'precio_unitario', 'impuesto_id',
                       'unidad_medida', 'precio_editable_en_emision']),
        ]);
    }
}
