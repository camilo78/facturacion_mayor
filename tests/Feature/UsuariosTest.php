<?php

namespace Tests\Feature;

use App\Actions\Facturacion\EmisorFactura;
use App\Models\CaiAutorizacion;
use App\Models\Establecimiento;
use App\Models\Impuesto;
use App\Models\PuntoEmision;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    private static bool $schemaListo = false;

    protected function setUp(): void
    {
        parent::setUp();

        // El schema se levanta UNA sola vez por proceso de PHPUnit.
        // Esto evita el problema de DDL dentro de transacciones en MariaDB.
        if (! self::$schemaListo) {
            Artisan::call('migrate:fresh');
            Artisan::call('migrate', ['--path' => 'database/migrations/tenant', '--realpath' => false]);
            self::$schemaListo = true;
        }

        // Limpiar datos entre tests (más predecible que transaction rollback con DDL)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        collect(['activity_log', 'facturas', 'detalle_facturas', 'cai_autorizaciones',
                 'puntos_emision', 'establecimientos', 'impuestos',
                 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
                 'roles', 'permissions', 'users'])
            ->each(fn ($t) => DB::table($t)->truncate());
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        (new RolesPermisosSeeder())->run();
    }

    // ─── Test 1: No dejar la empresa sin Admin ────────────────────────

    public function test_no_puede_desactivar_al_unico_admin(): void
    {
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('Admin');

        $adminsActivos = User::role('Admin')->where('activo', true)->count();
        $this->assertSame(1, $adminsActivos);

        // Replica lógica de GestionUsuarios::toggleActivo
        $puedeDesactivar = ! ($admin->activo && $admin->hasRole('Admin') && $adminsActivos <= 1);
        $this->assertFalse($puedeDesactivar);
    }

    public function test_puede_desactivar_admin_si_hay_otro(): void
    {
        $admin1 = User::factory()->create(['activo' => true]);
        $admin1->assignRole('Admin');
        $admin2 = User::factory()->create(['activo' => true]);
        $admin2->assignRole('Admin');

        $adminsActivos = User::role('Admin')->where('activo', true)->count();
        $puedeDesactivar = ! ($admin1->activo && $admin1->hasRole('Admin') && $adminsActivos <= 1);

        $this->assertTrue($puedeDesactivar);
    }

    public function test_no_puede_quitar_rol_admin_al_unico_admin(): void
    {
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('Admin');

        $adminsActivos = User::role('Admin')->where('activo', true)->count();
        $nuevoRol       = 'Cajero';

        // Replica lógica de FormUsuario::guardar
        $puedeEditar = ! ($admin->hasRole('Admin') && $nuevoRol !== 'Admin' && $adminsActivos <= 1);
        $this->assertFalse($puedeEditar);
    }

    // ─── Test 2: Permisos efectivos por rol ──────────────────────────

    public function test_cajero_solo_emite_y_ve_no_anula(): void
    {
        $cajero = User::factory()->create(['activo' => true]);
        $cajero->assignRole('Cajero');

        $this->assertTrue($cajero->can('facturas.emitir'));
        $this->assertTrue($cajero->can('facturas.ver'));
        $this->assertFalse($cajero->can('facturas.anular'));
        $this->assertFalse($cajero->can('usuarios.crear'));
        $this->assertFalse($cajero->can('productos.crear'));
    }

    public function test_supervisor_anula_pero_no_crea_usuarios(): void
    {
        $sup = User::factory()->create(['activo' => true]);
        $sup->assignRole('Supervisor de Caja');

        $this->assertTrue($sup->can('facturas.anular'));
        $this->assertTrue($sup->can('usuarios.ver'));
        $this->assertFalse($sup->can('usuarios.crear'));
        $this->assertFalse($sup->can('productos.crear'));
    }

    public function test_contador_gestiona_cai_pero_no_emite(): void
    {
        $contador = User::factory()->create(['activo' => true]);
        $contador->assignRole('Contador');

        $this->assertTrue($contador->can('cai.gestionar'));
        $this->assertTrue($contador->can('reportes.fiscales'));
        $this->assertFalse($contador->can('facturas.emitir'));
        $this->assertFalse($contador->can('facturas.anular'));
        $this->assertFalse($contador->can('usuarios.crear'));
    }

    public function test_admin_tiene_todos_los_permisos(): void
    {
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('Admin');

        foreach (collect(RolesPermisosSeeder::PERMISOS)->flatten() as $permiso) {
            $this->assertTrue($admin->can($permiso), "Admin debe tener: {$permiso}");
        }
    }

    // ─── Test 3: Emisión guarda emitida_por ──────────────────────────

    public function test_emision_guarda_emitida_por(): void
    {
        $emisor = User::factory()->create(['activo' => true]);
        $emisor->assignRole('Cajero');
        Auth::login($emisor);

        $matriz = Establecimiento::create([
            'codigo' => '000', 'nombre' => 'Casa Matriz', 'activo' => true,
        ]);
        $caja = PuntoEmision::create([
            'establecimiento_id' => $matriz->id,
            'codigo'   => '001',
            'nombre'   => 'Caja Test',
            'emisor_tipo' => 'mayor',
            'activo'   => true,
        ]);
        $impuesto = Impuesto::create([
            'codigo' => 'ISV15', 'nombre' => 'ISV 15%', 'tasa' => 15.00, 'es_default' => true,
        ]);
        CaiAutorizacion::create([
            'punto_emision_id'     => $caja->id,
            'tipo_documento'       => '01',
            'cai'                  => 'AAAA11-BBBB22-CCCC33-DDDD44-EEEE55-06',
            'rango_inicial'        => 1,
            'rango_final'          => 100,
            'correlativo_actual'   => 0,
            'fecha_limite_emision' => now()->addYear(),
            'activo'               => true,
        ]);

        $factura = (new EmisorFactura())->emitir([
            'punto_emision_id' => $caja->id,
            'nombre_cliente'   => 'Consumidor Final',
            'tipo_pago'        => 'contado',
            'lineas'           => [[
                'descripcion'     => 'Servicio de prueba',
                'cantidad'        => 1,
                'precio_unitario' => 100.00,
                'descuento'       => 0,
                'impuesto_id'     => $impuesto->id,
                'unidad_medida'   => 'unidad',
            ]],
        ]);

        $this->assertSame($emisor->id, $factura->emitida_por);
        $this->assertSame('VIGENTE', $factura->estado);
        $this->assertSame('000-001-01-00000001', $factura->numero_completo);
    }
}
