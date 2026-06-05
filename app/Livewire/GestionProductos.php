<?php

namespace App\Livewire;

use App\Models\Impuesto;
use App\Models\Producto;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Productos')]
class GestionProductos extends Component
{
    use WithPagination, WithFileUploads;

    public string $busqueda    = '';
    public string $filtroTipo  = '';
    public bool   $soloActivos = true;

    public bool   $mostrarModal = false;
    public ?int   $editandoId  = null;

    // Campos del formulario
    public string  $codigo                = '';
    public string  $codigoBarras          = '';
    public string  $descripcion           = '';
    public string  $descripcionLarga      = '';
    public string  $tipo                  = 'bien';
    public string  $categoria             = '';
    public string  $marca                 = '';
    public string  $unidadMedida          = 'unidad';
    public ?string $precioUnitario        = null;
    public ?string $precioCompra          = null;
    public ?int    $impuestoId            = null;
    public bool    $incluyeIsv            = false;
    public bool    $controlaInventario    = false;
    public bool    $precioEditableEmision = true;
    public bool    $activo                = true;
    public string  $notas                 = '';

    // Imagen
    public ?string $imagenActual  = null;
    public $imagenTemporal        = null;
    public bool    $quitarImagen  = false;

    // Tab activa del modal — persiste durante re-renders de Livewire
    public string $tabActiva = 'principal';

    public function updatedBusqueda(): void   { $this->resetPage(); }
    public function updatedFiltroTipo(): void { $this->resetPage(); }

    public function updatedTipo(): void
    {
        if ($this->tipo === 'servicio') {
            // Los servicios no se inventarían
            $this->controlaInventario = false;

            // Si el ISV seleccionado es > 15% (aplica solo a bienes selectivos),
            // resetear al ISV 15% o dejarlo en null si no existe
            if ($this->impuestoId) {
                $imp = Impuesto::find($this->impuestoId);
                if ($imp && (float) $imp->tasa > 15.00) {
                    $this->impuestoId = Impuesto::where('activo', true)
                        ->where('tasa', '<=', 15.00)
                        ->where('tasa', '>', 0)
                        ->orderByDesc('tasa')
                        ->value('id');
                }
            }

            // Sugerencia de unidad para servicios si tiene la de bienes
            if (in_array($this->unidadMedida, ['unidad', 'kg', 'litro', 'metro', 'caja', 'libra'])) {
                $this->unidadMedida = 'hora';
            }
        } else {
            // Volviendo a bien
            if (in_array($this->unidadMedida, ['hora', 'día', 'mes', 'proyecto'])) {
                $this->unidadMedida = 'unidad';
            }
        }
    }

    // ── Imagen ──────────────────────────────────────────────────────

    public function updatedImagenTemporal(): void
    {
        $this->validate(['imagenTemporal' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192']]);
        $this->quitarImagen = false;
    }

    public function eliminarImagen(): void
    {
        $this->imagenTemporal = null;
        $this->quitarImagen   = true;
    }

    // ── CRUD ────────────────────────────────────────────────────────

    public function abrirCrear(): void
    {
        $this->reset([
            'codigo', 'codigoBarras', 'descripcion', 'descripcionLarga',
            'categoria', 'marca', 'precioUnitario', 'precioCompra',
            'impuestoId', 'notas', 'editandoId', 'imagenActual', 'imagenTemporal',
        ]);
        $this->tipo                  = 'bien';
        $this->unidadMedida          = 'unidad';
        $this->incluyeIsv            = false;
        $this->controlaInventario    = false;
        $this->precioEditableEmision = true;
        $this->activo                = true;
        $this->quitarImagen          = false;
        $this->tabActiva             = 'principal';
        $this->mostrarModal          = true;
        $this->resetValidation();
    }

    public function abrirEditar(int $id): void
    {
        $p = Producto::findOrFail($id);
        $this->editandoId            = $id;
        $this->codigo                = $p->codigo;
        $this->codigoBarras          = $p->codigo_barras ?? '';
        $this->descripcion           = $p->descripcion;
        $this->descripcionLarga      = $p->descripcion_larga ?? '';
        $this->tipo                  = $p->tipo ?? 'bien';
        $this->categoria             = $p->categoria ?? '';
        $this->marca                 = $p->marca ?? '';
        $this->unidadMedida          = $p->unidad_medida ?? 'unidad';
        $this->precioUnitario        = $p->precio_unitario !== null ? (string) $p->precio_unitario : null;
        $this->precioCompra          = $p->precio_compra  !== null ? (string) $p->precio_compra  : null;
        $this->impuestoId            = $p->impuesto_id;
        $this->incluyeIsv            = (bool) $p->incluye_isv;
        $this->controlaInventario    = (bool) $p->controla_inventario;
        $this->precioEditableEmision = (bool) $p->precio_editable_en_emision;
        $this->activo                = (bool) $p->activo;
        $this->notas                 = $p->notas ?? '';
        $this->imagenActual          = $p->imagen;
        $this->imagenTemporal        = null;
        $this->quitarImagen          = false;
        $this->tabActiva             = 'principal';
        $this->mostrarModal          = true;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->validate([
            'codigo'          => ['required', 'string', 'max:50'],
            'descripcion'     => ['required', 'string', 'max:255'],
            'precioUnitario'  => ['required', 'numeric', 'min:0'],
            'tipo'            => ['required', 'in:bien,servicio'],
            'unidadMedida'    => ['required', 'string', 'max:20'],
            'imagenTemporal'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ]);

        $imagenPath = $this->procesarImagen();

        $datos = [
            'codigo'                     => strtoupper(trim($this->codigo)),
            'codigo_barras'              => trim($this->codigoBarras) ?: null,
            'descripcion'                => trim($this->descripcion),
            'descripcion_larga'          => trim($this->descripcionLarga) ?: null,
            'tipo'                       => $this->tipo,
            'categoria'                  => trim($this->categoria) ?: null,
            'marca'                      => trim($this->marca) ?: null,
            'unidad_medida'              => $this->unidadMedida,
            'precio_unitario'            => $this->precioUnitario ?? 0,
            'precio_compra'              => $this->precioCompra ?: null,
            'impuesto_id'                => $this->impuestoId,
            'incluye_isv'                => $this->incluyeIsv,
            'controla_inventario'        => $this->controlaInventario,
            'precio_editable_en_emision' => $this->precioEditableEmision,
            'activo'                     => $this->activo,
            'notas'                      => trim($this->notas) ?: null,
            'imagen'                     => $imagenPath,
        ];

        if ($this->editandoId) {
            Producto::findOrFail($this->editandoId)->update($datos);
            $msg = 'Producto actualizado.';
        } else {
            Producto::create($datos);
            $msg = 'Producto creado.';
        }

        $this->mostrarModal = false;
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    private function procesarImagen(): ?string
    {
        // 1. Sin imagen nueva y se pidió quitar
        if ($this->quitarImagen) {
            $this->borrarArchivoImagen($this->imagenActual);
            return null;
        }

        // 2. Sin imagen nueva — mantener la existente
        if (! $this->imagenTemporal) {
            return $this->imagenActual;
        }

        // 3. Imagen nueva — borrar anterior, procesar y guardar
        $this->borrarArchivoImagen($this->imagenActual);

        $filename = Str::uuid() . '.webp';
        $dir      = 'img/' . tenant('id') . '/productos';
        $fullDir  = public_path($dir);

        if (! is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        (new ImageManager(new Driver()))
            ->decode($this->imagenTemporal->getRealPath())
            ->scaleDown(width: 600, height: 600)
            ->encode(new WebpEncoder(quality: 75))
            ->save(public_path("{$dir}/{$filename}"));

        return "{$dir}/{$filename}";
    }

    private function borrarArchivoImagen(?string $path): void
    {
        if ($path && file_exists(public_path($path))) {
            unlink(public_path($path));
        }
    }

    public function toggleActivo(int $id): void
    {
        $p = Producto::findOrFail($id);
        $p->update(['activo' => ! $p->activo]);
    }

    public function render(): View
    {
        $query = Producto::with('impuesto')->orderBy('descripcion');

        if ($this->soloActivos) {
            $query->where('activo', true);
        }

        if ($this->filtroTipo) {
            $query->where('tipo', $this->filtroTipo);
        }

        if ($this->busqueda) {
            $b = $this->busqueda;
            $query->where(fn ($q) => $q
                ->where('descripcion', 'like', "%$b%")
                ->orWhere('codigo', 'like', "%$b%")
                ->orWhere('codigo_barras', 'like', "%$b%")
            );
        }

        return view('livewire.gestion-productos', [
            'productos' => $query->paginate(25),
            'impuestos' => Impuesto::where('activo', true)->orderBy('tasa')->get(),
        ]);
    }
}
