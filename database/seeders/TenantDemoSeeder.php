<?php

namespace Database\Seeders;

use App\Models\CaiAutorizacion;
use App\Models\Cliente;
use App\Models\Establecimiento;
use App\Models\Impuesto;
use App\Models\Producto;
use App\Models\PuntoEmision;
use Illuminate\Database\Seeder;

class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        $isv15  = Impuesto::where('tasa', 15)->first();
        $isv18  = Impuesto::where('tasa', 18)->first();
        $exento = Impuesto::where('tasa', 0)->first();

        // --- Establecimientos adicionales ---
        $sucursal = Establecimiento::firstOrCreate(
            ['codigo' => '001'],
            ['nombre' => 'Sucursal Centro', 'direccion' => 'Av. Central 45, Tegucigalpa', 'activo' => true]
        );

        // --- Cajas adicionales ---
        $casaMatriz = Establecimiento::where('codigo', '000')->first();

        if ($casaMatriz) {
            PuntoEmision::firstOrCreate(
                ['establecimiento_id' => $casaMatriz->id, 'codigo' => '002'],
                ['nombre' => 'Caja 2', 'emisor_tipo' => 'mayor', 'activo' => true]
            );
        }

        PuntoEmision::firstOrCreate(
            ['establecimiento_id' => $sucursal->id, 'codigo' => '001'],
            ['nombre' => 'Caja Sucursal', 'emisor_tipo' => 'mayor', 'activo' => true]
        );

        // CAI de demo para la caja de sucursal (si la caja existe y no tiene CAI)
        $cajaSucursal = PuntoEmision::where('establecimiento_id', $sucursal->id)
            ->where('codigo', '001')->first();

        if ($cajaSucursal && ! CaiAutorizacion::where('punto_emision_id', $cajaSucursal->id)->exists()) {
            CaiAutorizacion::create([
                'punto_emision_id'     => $cajaSucursal->id,
                'tipo_documento'       => '01',
                'cai'                  => 'DEMO11-222222-DEMO33-444444-DEMO55-06',
                'rango_inicial'        => 1,
                'rango_final'          => 5000,
                'correlativo_actual'   => 0,
                'fecha_limite_emision' => now()->addYear(),
                'activo'               => true,
            ]);
        }

        // --- Clientes ---
        $clientes = [
            [
                'rtn'       => '05011990123456',
                'nombre'    => 'Juan Pérez López',
                'direccion' => 'Col. Kennedy, Bloque C, Casa 14, Tegucigalpa',
                'email'     => 'juan@ejemplo.hn',
                'telefono'  => '9991-0001',
            ],
            [
                'rtn'       => '05011985654321',
                'nombre'    => 'María García Martínez',
                'direccion' => 'Res. Los Laureles, Calle 5, Casa 28, San Pedro Sula',
                'email'     => 'maria@ejemplo.hn',
                'telefono'  => '9991-0002',
            ],
            [
                'rtn'       => '08011978112233',
                'nombre'    => 'Empresa ABC S.A. de C.V.',
                'direccion' => 'Col. Palmira, Av. La Paz, Edificio Torres, Tegucigalpa',
                'email'     => 'abc@empresa.hn',
                'telefono'  => '2222-0001',
            ],
            [
                'rtn'       => '05011992334455',
                'nombre'    => 'Carlos Rodríguez Soto',
                'direccion' => 'Barrio Las Minitas, 3ra Calle, Casa 7, Comayagüela',
                'email'     => null,
                'telefono'  => '9991-0003',
            ],
            [
                'rtn'       => '05011988778899',
                'nombre'    => 'Ana Flores Domínguez',
                'direccion' => 'Col. Trejo, Bloque 2, Apto 5, La Ceiba',
                'email'     => 'ana@ejemplo.hn',
                'telefono'  => null,
            ],
        ];

        foreach ($clientes as $c) {
            Cliente::updateOrCreate(
                ['rtn' => $c['rtn']],
                array_merge($c, ['activo' => true])
            );
        }

        // --- Productos ---
        $productos = [
            // ── Tecnología ──────────────────────────────────────────────
            [
                'codigo'                     => 'SERV-001',
                'descripcion'                => 'Hora de consultoría técnica',
                'tipo'                       => 'servicio',
                'unidad_medida'              => 'hora',
                'precio_unitario'            => 500.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'SERV-002',
                'descripcion'                => 'Soporte técnico mensual',
                'tipo'                       => 'servicio',
                'unidad_medida'              => 'mes',
                'precio_unitario'            => 2500.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'SERV-003',
                'descripcion'                => 'Licencia software anual',
                'tipo'                       => 'servicio',
                'unidad_medida'              => 'año',
                'precio_unitario'            => 8500.00,
                'impuesto_id'                => $isv18?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'SERV-004',
                'descripcion'                => 'Instalación de red LAN',
                'tipo'                       => 'servicio',
                'unidad_medida'              => 'servicio',
                'precio_unitario'            => 3200.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'SERV-005',
                'descripcion'                => 'Diseño de sitio web',
                'tipo'                       => 'servicio',
                'unidad_medida'              => 'proyecto',
                'precio_unitario'            => 15000.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BIEN-001',
                'descripcion'                => 'Teclado mecánico USB',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 1200.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BIEN-002',
                'descripcion'                => 'Mouse inalámbrico',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 450.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BIEN-003',
                'descripcion'                => 'Cable HDMI 2m',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 150.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BIEN-004',
                'descripcion'                => 'Monitor LED 24" Full HD',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 7800.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BIEN-005',
                'descripcion'                => 'Laptop HP 15.6" Core i5',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 22500.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BIEN-006',
                'descripcion'                => 'Impresora térmica 80mm',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 3500.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BIEN-007',
                'descripcion'                => 'Router WiFi doble banda',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 1850.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BIEN-008',
                'descripcion'                => 'Disco duro externo 1TB USB 3.0',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 2200.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BIEN-009',
                'descripcion'                => 'Memoria USB 64GB',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 220.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BIEN-010',
                'descripcion'                => 'Auriculares Bluetooth',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 980.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            // ── Papelería / Oficina ──────────────────────────────────────
            [
                'codigo'                     => 'PAP-001',
                'descripcion'                => 'Resma papel bond carta',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'resma',
                'precio_unitario'            => 185.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'PAP-002',
                'descripcion'                => 'Bolígrafo azul (caja 12 unidades)',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'caja',
                'precio_unitario'            => 65.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'PAP-003',
                'descripcion'                => 'Archivador palanca tamaño carta',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 95.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'PAP-004',
                'descripcion'                => 'Cartucho tinta negra HP 664',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 420.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            // ── Alimentos (exentos ISV) ──────────────────────────────────
            [
                'codigo'                     => 'ALI-001',
                'descripcion'                => 'Agua purificada 500ml',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 15.00,
                'impuesto_id'                => $exento?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'ALI-002',
                'descripcion'                => 'Café molido 454g',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'bolsa',
                'precio_unitario'            => 120.00,
                'impuesto_id'                => $exento?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'ALI-003',
                'descripcion'                => 'Pan de molde blanco',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'paquete',
                'precio_unitario'            => 48.00,
                'impuesto_id'                => $exento?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'ALI-004',
                'descripcion'                => 'Leche entera 1 litro',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'litro',
                'precio_unitario'            => 32.00,
                'impuesto_id'                => $exento?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'ALI-005',
                'descripcion'                => 'Arroz blanco 2 libras',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'bolsa',
                'precio_unitario'            => 42.00,
                'impuesto_id'                => $exento?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            // ── Bebidas (ISV 18%) ────────────────────────────────────────
            [
                'codigo'                     => 'BEB-001',
                'descripcion'                => 'Refresco cola 355ml',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 28.00,
                'impuesto_id'                => $isv18?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BEB-002',
                'descripcion'                => 'Cerveza nacional 355ml',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 38.00,
                'impuesto_id'                => $isv18?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'BEB-003',
                'descripcion'                => 'Jugo natural naranja 1L',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'litro',
                'precio_unitario'            => 55.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            // ── Limpieza ─────────────────────────────────────────────────
            [
                'codigo'                     => 'LIM-001',
                'descripcion'                => 'Detergente en polvo 1kg',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'bolsa',
                'precio_unitario'            => 75.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'LIM-002',
                'descripcion'                => 'Cloro líquido 1 galón',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'galón',
                'precio_unitario'            => 58.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'LIM-003',
                'descripcion'                => 'Papel higiénico (paquete 4 rollos)',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'paquete',
                'precio_unitario'            => 68.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            // ── Ferretería ───────────────────────────────────────────────
            [
                'codigo'                     => 'FER-001',
                'descripcion'                => 'Pintura látex blanca galón',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'galón',
                'precio_unitario'            => 380.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'FER-002',
                'descripcion'                => 'Tubo PVC 1/2" x 6m',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 95.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'FER-003',
                'descripcion'                => 'Cable eléctrico calibre 12 (metro)',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'metro',
                'precio_unitario'            => 28.50,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            // ── Ropa / Textiles ──────────────────────────────────────────
            [
                'codigo'                     => 'ROT-001',
                'descripcion'                => 'Camiseta algodón talla M',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 180.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'ROT-002',
                'descripcion'                => 'Pantalón jean talla 32',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 420.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'ROT-003',
                'descripcion'                => 'Zapatos deportivos talla 42',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'par',
                'precio_unitario'            => 850.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            // ── Farmacia / Salud (exento) ────────────────────────────────
            [
                'codigo'                     => 'FAR-001',
                'descripcion'                => 'Acetaminofén 500mg (caja 24 tabletas)',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'caja',
                'precio_unitario'            => 45.00,
                'impuesto_id'                => $exento?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'FAR-002',
                'descripcion'                => 'Alcohol isopropílico 70% 1L',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'litro',
                'precio_unitario'            => 88.00,
                'impuesto_id'                => $exento?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'FAR-003',
                'descripcion'                => 'Mascarilla quirúrgica (caja 50 unidades)',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'caja',
                'precio_unitario'            => 120.00,
                'impuesto_id'                => $exento?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            // ── Electrodomésticos ────────────────────────────────────────
            [
                'codigo'                     => 'EDO-001',
                'descripcion'                => 'Microondas 0.9 pies cúbicos',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 3800.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'EDO-002',
                'descripcion'                => 'Licuadora 3 velocidades',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 950.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'EDO-003',
                'descripcion'                => 'Ventilador de mesa 12"',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 680.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'EDO-004',
                'descripcion'                => 'Plancha de ropa vapor',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 520.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            // ── Servicios profesionales ──────────────────────────────────
            [
                'codigo'                     => 'SRP-001',
                'descripcion'                => 'Servicio de contabilidad mensual',
                'tipo'                       => 'servicio',
                'unidad_medida'              => 'mes',
                'precio_unitario'            => 4500.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'SRP-002',
                'descripcion'                => 'Asesoría legal por hora',
                'tipo'                       => 'servicio',
                'unidad_medida'              => 'hora',
                'precio_unitario'            => 1200.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'SRP-003',
                'descripcion'                => 'Diseño gráfico por pieza',
                'tipo'                       => 'servicio',
                'unidad_medida'              => 'pieza',
                'precio_unitario'            => 800.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'SRP-004',
                'descripcion'                => 'Mantenimiento preventivo PC',
                'tipo'                       => 'servicio',
                'unidad_medida'              => 'servicio',
                'precio_unitario'            => 350.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            // ── Materiales de construcción ───────────────────────────────
            [
                'codigo'                     => 'CON-001',
                'descripcion'                => 'Bolsa de cemento 42.5 kg',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'bolsa',
                'precio_unitario'            => 285.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'CON-002',
                'descripcion'                => 'Varilla hierro 3/8" x 6m',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 195.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'CON-003',
                'descripcion'                => 'Bloque de concreto 15cm',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 18.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            // ── Automotriz ───────────────────────────────────────────────
            [
                'codigo'                     => 'AUT-001',
                'descripcion'                => 'Aceite motor 20W-50 (cuarto)',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'cuarto',
                'precio_unitario'            => 145.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => false,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'AUT-002',
                'descripcion'                => 'Filtro de aceite universal',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 220.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
            [
                'codigo'                     => 'AUT-003',
                'descripcion'                => 'Batería vehicular 12V 60Ah',
                'tipo'                       => 'bien',
                'unidad_medida'              => 'unidad',
                'precio_unitario'            => 2800.00,
                'impuesto_id'                => $isv15?->id,
                'precio_editable_en_emision' => true,
                'activo'                     => true,
            ],
        ];

        foreach ($productos as $p) {
            Producto::firstOrCreate(['codigo' => $p['codigo']], $p);
        }
    }
}
