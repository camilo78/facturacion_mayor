<?php

namespace App\Console\Commands;

use App\Models\CaiAutorizacion;
use App\Models\Cliente;
use App\Models\Establecimiento;
use App\Models\Impuesto;
use App\Models\Instance;
use App\Models\Producto;
use App\Models\PuntoEmision;
use App\Models\SyncQueue;
use App\Services\ConnectivityChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SyncPull extends Command
{
    protected $signature = 'sync:pull {--force : Descarga completa ignorando último timestamp}';
    protected $description = 'Descarga datos maestros del Mayor y los aplica localmente';

    private const MODEL_MAP = [
        'establecimientos'   => Establecimiento::class,
        'puntos_emision'     => PuntoEmision::class,
        'cai_autorizaciones' => CaiAutorizacion::class,
        'impuestos'          => Impuesto::class,
        'clientes'           => Cliente::class,
        'productos'          => Producto::class,
    ];

    // Claves de relaciones eager cargadas por el Mayor que no van en el upsert
    private const STRIP_KEYS = [
        'productos' => ['impuesto'],
    ];

    public function handle(): int
    {
        if (! config('instance.is_auxiliar')) {
            $this->warn('Este comando solo aplica en nodos Auxiliar.');
            return 0;
        }

        $instance = Instance::find(config('instance.uuid'));
        if (! $instance || ! $instance->tenant_id) {
            $this->error('Instancia no registrada o sin tenant asignado.');
            return 1;
        }
        tenancy()->initialize($instance->tenant_id);

        // Respetar backoff compartido con sync:push
        if (! $this->option('force')) {
            $blockedUntil = Cache::get('sync:blocked_until');
            if ($blockedUntil && now()->lt($blockedUntil)) {
                $this->info("Backoff activo hasta {$blockedUntil->format('H:i:s')}, saltando.");
                return 0;
            }
        }

        if (! ConnectivityChecker::mayorReachable()) {
            $this->warn('Mayor no alcanzable. Abortando sync:pull.');
            return 1;
        }

        $mayorUrl   = rtrim(config('instance.mayor_url'), '/');
        $mayorToken = config('instance.mayor_token');
        $desde      = $this->option('force') ? null : Cache::get('sync:last_pull_at');

        $this->info('Solicitando datos al Mayor' . ($desde ? " (desde {$desde})" : ' (sincronización completa)') . '...');

        try {
            $payload = ['tablas' => array_keys(self::MODEL_MAP)];
            if ($desde) {
                $payload['desde'] = $desde;
            }

            $response = Http::withToken($mayorToken)
                ->timeout(60)
                ->post("{$mayorUrl}/api/sync/pull", $payload);

            if (! $response->successful()) {
                $this->error("Error del Mayor: HTTP {$response->status()}");
                return 1;
            }

            $body = $response->json();

            $this->aplicarDatos($body['tablas'] ?? []);

            // Guardar timestamp para el próximo pull delta
            Cache::put('sync:last_pull_at', $body['hasta'], now()->addDays(30));

            $totales = collect($body['totales'] ?? [])
                ->map(fn ($n, $t) => "{$t}:{$n}")
                ->implode(' ');
            $this->info("Pull completo. {$totales}");

            return 0;
        } catch (\Throwable $e) {
            $this->error("Excepción en sync:pull: {$e->getMessage()}");
            return 1;
        }
    }

    private function aplicarDatos(array $tablas): void
    {
        SyncQueue::suspender(function () use ($tablas) {
            foreach ($tablas as $tabla => $registros) {
                $modelClass = self::MODEL_MAP[$tabla] ?? null;
                if (! $modelClass || empty($registros)) {
                    continue;
                }

                $strip = self::STRIP_KEYS[$tabla] ?? [];
                $count = 0;

                foreach ($registros as $row) {
                    $uuid = $row['uuid'] ?? null;
                    if (! $uuid) {
                        continue;
                    }

                    // Quitar relaciones anidadas que no son columnas de la tabla
                    foreach ($strip as $key) {
                        unset($row[$key]);
                    }

                    // Quitar timestamps: updateOrCreate los maneja; synced_at lo ponemos nosotros
                    unset($row['id'], $row['created_at'], $row['updated_at']);

                    $modelClass::updateOrCreate(
                        ['uuid' => $uuid],
                        array_merge($row, ['synced_at' => now()])
                    );

                    $count++;
                }

                $this->line("  {$tabla}: {$count} registros.");
            }
        });
    }
}
