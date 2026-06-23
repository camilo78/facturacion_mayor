<?php

namespace App\Console\Commands;

use App\Models\Instance;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class InstanceBootstrap extends Command
{
    protected $signature   = 'instance:bootstrap
                               {--fresh : Re-descarga todos los datos del Mayor aunque ya existan}';
    protected $description = 'Inicializa el Auxiliar: crea el tenant local, migra y descarga datos del Mayor';

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

        // [6] Si la instancia ya está registrada y no se pidió fresh → nada que hacer
        $instanciaLocal = Instance::find($uuid);
        if ($instanciaLocal && ! $this->option('fresh')) {
            $this->info("Instancia ya inicializada: {$instanciaLocal->label}");
            $this->line('  Usa --fresh para re-sincronizar desde cero.');
            return 0;
        }

        // ── 1. Obtener info del Mayor ──────────────────────────────────────────
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

        // ── 2. Crear/actualizar tenant local ─────────────────────────────────
        $this->line('Provisionando tenant local...');

        // [6] firstOrCreate es idempotente: si el tenant ya existe (reinicio tras fallo
        // parcial), no dispara TenantCreated ni intenta crear la DB dos veces.
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

        // Garantizar que las migraciones del tenant estén al día en cualquier caso
        $this->line('  Aplicando migraciones del tenant...');
        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenantId],
            '--force'   => true,
        ]);

        // ── 3. Registrar instancia local ──────────────────────────────────────
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

        // ── 4. Pull completo de datos maestros + usuarios ─────────────────────
        $this->line('Descargando datos del Mayor (pull completo)...');
        $exitCode = Artisan::call('sync:pull', ['--force' => true], $this->output);

        if ($exitCode !== 0) {
            $this->warn('El pull tuvo errores. Reintenta con: php artisan sync:pull --force');
        }

        $this->newLine();
        $this->info('Bootstrap completado. El Auxiliar está listo.');
        return 0;
    }

    /**
     * Crear o encontrar el tenant de forma idempotente.
     * Devuelve [$tenant, $esNuevo].
     */
    private function upsertTenant(string $tenantId, array $tenInfo): array
    {
        $existing = Tenant::find($tenantId);
        if ($existing) {
            return [$existing, false];
        }

        // Tenant::create() dispara TenantCreated → crea la DB + migra.
        // Solo llega aquí si el tenant NO existe todavía.
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
