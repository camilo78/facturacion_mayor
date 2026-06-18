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
            $this->line('  INSTANCE_UUID     → UUID generado por instance:registrar en el Mayor');
            $this->line('  MAYOR_SYNC_TOKEN  → Token generado por instance:registrar en el Mayor');
            $this->line('  MAYOR_SYNC_URL    → URL del Mayor (ej: https://factunet.io)');
            return 1;
        }

        // Si la instancia ya existe localmente y no se pide fresh → nada que hacer
        $instanciaLocal = Instance::find($uuid);
        if ($instanciaLocal && ! $this->option('fresh')) {
            $this->info("Instancia ya inicializada: {$instanciaLocal->label}");
            $this->line('  Usa --fresh para re-sincronizar desde cero.');
            return 0;
        }

        // ── 1. Obtener info del Mayor ──────────────────────────────────────
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

        // ── 2. Crear/actualizar tenant local ───────────────────────────────
        $this->line('Provisionando tenant local...');
        $esNuevo = ! Tenant::find($tenantId);

        if ($esNuevo) {
            // Tenant::create() en stancl/tenancy dispara eventos que:
            //   → crean la base de datos del tenant
            //   → ejecutan las migraciones del tenant automáticamente
            Tenant::create([
                'id'               => $tenantId,
                'nombre'           => $tenInfo['nombre'],
                'nombre_comercial' => $tenInfo['nombre_comercial'],
                'rtn'              => $tenInfo['rtn'],
                'email'            => $tenInfo['email'],
                'telefono'         => $tenInfo['telefono'],
                'plan'             => $tenInfo['plan']   ?? 'basico',
                'estado'           => $tenInfo['estado'] ?? 'activo',
            ]);
            $this->info('  Base de datos del tenant creada y migrada.');
        } else {
            // Actualizar datos básicos de la empresa
            Tenant::find($tenantId)->update([
                'nombre'           => $tenInfo['nombre'],
                'nombre_comercial' => $tenInfo['nombre_comercial'],
            ]);
            $this->info('  Tenant ya existía. Datos actualizados.');
        }

        // ── 3. Registrar instancia local ───────────────────────────────────
        $this->line('Registrando instancia local...');
        Instance::updateOrCreate(
            ['id' => $uuid],
            [
                'tipo'               => 'auxiliar',
                'tenant_id'          => $tenantId,
                'establecimiento_id' => $instInfo['establecimiento_id'],
                'label'              => $instInfo['label'],
                'activo'             => true,
            ]
        );
        $this->info("  Instancia registrada: {$instInfo['label']}");

        // ── 4. Pull completo de datos maestros ─────────────────────────────
        $this->line('Descargando datos del Mayor (pull completo)...');
        $exitCode = Artisan::call('sync:pull', ['--force' => true], $this->output);

        if ($exitCode !== 0) {
            $this->warn('El pull falló o tuvo errores. Puedes reintentarlo con: php artisan sync:pull --force');
        }

        $this->newLine();
        $this->info('Bootstrap completado. El Auxiliar está listo.');
        return 0;
    }
}
