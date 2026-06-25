<?php

namespace App\Console\Commands;

use App\Models\Instance;
use App\Models\Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;

class InstanceBootstrap extends Command
{
    protected $signature   = 'instance:bootstrap
                               {--fresh : Re-descarga todos los datos del Mayor aunque ya existan}';
    protected $description = 'Inicializa el Auxiliar: crea el tenant local, migra, siembra permisos y descarga datos del Mayor';

    public function handle(): int
    {
        if (! config('instance.is_auxiliar')) {
            $this->warn('Este comando solo aplica en nodos Auxiliar (INSTANCE_MODE=auxiliar).');
            return 0;
        }

        $uuid     = config('instance.uuid');
        $token    = config('instance.mayor_token');
        $mayorUrl = config('instance.mayor_url');

        if (! $uuid || ! $token || ! $mayorUrl) {
            $this->error('Faltan variables de entorno:');
            $this->line('  INSTANCE_UUID     → UUID generado en el Mayor');
            $this->line('  MAYOR_SYNC_TOKEN  → Token de sincronización del Mayor');
            $this->line('  MAYOR_SYNC_URL    → URL del Mayor (ej: https://factunet.io)');
            return 1;
        }

        // ── [12] FIX: "ya inicializada" se decide por ESTADO REAL DE LA BD, ──────
        // no por el UUID del .env (que sobrevive al borrado de volúmenes).
        // Si el registro de instancia existe PERO la BD del tenant está vacía,
        // se trata como instalación nueva y se fuerza pull completo.
        $instanciaLocal = Instance::find($uuid);
        if ($instanciaLocal && ! $this->option('fresh')) {
            $tenantIdLocal = $instanciaLocal->tenant_id;
            if ($tenantIdLocal && $this->tenantTieneData($tenantIdLocal)) {
                $this->info("Instancia ya inicializada: {$instanciaLocal->label}");
                $this->line('  Usa --fresh para re-sincronizar desde cero.');
                return 0;
            }
            $this->warn('Instancia registrada pero BD del tenant vacía — forzando provisión completa.');
        }

        // ── 1. Obtener info del Mayor ──────────────────────────────────────────────
        $this->info('Conectando con el Mayor...');
        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->get(rtrim($mayorUrl, '/') . '/api/sync/info');

            if (! $response->successful()) {
                $this->error("El Mayor devolvió HTTP {$response->status()}");
                return 1;
            }
        } catch (\Throwable $e) {
            $this->error("No se pudo conectar con el Mayor: {$e->getMessage()}");
            return 1;
        }

        $info     = $response->json();
        $tenInfo  = $info['tenant'];
        $instInfo = $info['instance'];
        $tenantId = $tenInfo['id'];

        $this->info("Empresa: {$tenInfo['nombre']} (ID: {$tenantId})");

        // ── 2. Crear/actualizar tenant local (idempotente) ────────────────────────
        $this->line('Provisionando tenant local...');
        [$tenant, $esNuevo] = $this->upsertTenant($tenantId, $tenInfo);

        if ($esNuevo) {
            $this->info('  Base de datos del tenant creada y migrada.');
        } else {
            $tenant->update([
                'nombre'           => $tenInfo['nombre'],
                'nombre_comercial' => $tenInfo['nombre_comercial'],
            ]);
            $this->info('  Tenant ya existía — datos actualizados.');
        }

        // Garantizar que las migraciones del tenant estén al día
        $this->line('  Aplicando migraciones del tenant...');
        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenantId],
            '--force'   => true,
        ]);

        // ── 3. Registrar instancia local ──────────────────────────────────────────
        $this->line('Registrando instancia local...');
        Instance::updateOrCreate(
            ['id' => $uuid],
            [
                'tipo'               => 'auxiliar',
                'tenant_id'          => $tenantId,
                'establecimiento_id' => $instInfo['establecimiento_id'] ?? null,
                'label'              => $instInfo['label'],
                'activo'             => true,
            ]
        );
        $this->info("  Instancia registrada: {$instInfo['label']}");

        // ── 4. [13] FIX: Sembrar permisos y roles DENTRO del tenant ──────────────
        // RolesPermisosSeeder crea los 4 roles con sus permisos asignados.
        // Debe correr ANTES de sync:pull para que cuando pullUsuarios() llame
        // syncRoles(), los roles ya existan con sus permisos completos.
        // El seeder es idempotente (firstOrCreate + syncPermissions).
        $this->line('Sembrando permisos y roles en el tenant...');
        tenancy()->initialize($tenantId);
        (new RolesPermisosSeeder())->run();
        $this->info('  Permisos y roles sembrados.');

        // ── 5. Pull completo de catálogos + usuarios ──────────────────────────────
        $this->line('Descargando datos del Mayor (pull completo)...');
        $exitCode = Artisan::call('sync:pull', ['--force' => true], $this->output);

        // ── 6. [13] Limpiar caché de Spatie tras asignar roles a usuarios ─────────
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        tenancy()->end();

        if ($exitCode !== 0) {
            $this->warn('El pull tuvo errores. Reintenta con: php artisan sync:pull --force');
        }

        $this->newLine();
        $this->info('Bootstrap completado. El Auxiliar está listo.');
        return 0;
    }

    /**
     * [12] Verifica si la BD del tenant tiene datos reales (catálogos o usuarios).
     * Usa try/catch porque el tenant puede no existir aún en el primer arranque.
     */
    private function tenantTieneData(string $tenantId): bool
    {
        try {
            tenancy()->initialize($tenantId);
            $count = DB::table('productos')->count()
                   + DB::table('clientes')->count()
                   + DB::table('users')->count();
            tenancy()->end();
            return $count > 0;
        } catch (\Throwable) {
            try { tenancy()->end(); } catch (\Throwable) {}
            return false;
        }
    }

    /**
     * Crear o encontrar el tenant de forma idempotente.
     * Si el tenant ya existe, no se dispara TenantCreated (no intenta crear la DB dos veces).
     * Devuelve [$tenant, $esNuevo].
     */
    private function upsertTenant(string $tenantId, array $tenInfo): array
    {
        $existing = Tenant::find($tenantId);
        if ($existing) {
            return [$existing, false];
        }

        $tenant = Tenant::create([
            'id'               => $tenantId,
            'nombre'           => $tenInfo['nombre'],
            'nombre_comercial' => $tenInfo['nombre_comercial'],
            'rtn'              => $tenInfo['rtn']      ?? null,
            'email'            => $tenInfo['email']    ?? null,
            'telefono'         => $tenInfo['telefono'] ?? null,
            'plan'             => $tenInfo['plan']     ?? 'basico',
            'estado'           => $tenInfo['estado']   ?? 'activo',
        ]);

        return [$tenant, true];
    }
}
