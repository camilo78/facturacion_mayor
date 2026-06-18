<?php

namespace App\Console\Commands;

use App\Models\Instance;
use App\Models\SyncQueue;
use App\Services\ConnectivityChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SyncPush extends Command
{
    protected $signature = 'sync:push';
    protected $description = 'Envía registros pendientes de la cola al Mayor';

    private const BATCH_SIZE = 50;

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

        // Backoff: si el Mayor estuvo inaccesible, esperar antes de reintentar
        $blockedUntil = Cache::get('sync:blocked_until');
        if ($blockedUntil && now()->lt($blockedUntil)) {
            $this->info("Backoff activo hasta {$blockedUntil->format('H:i:s')}, saltando.");
            return 0;
        }

        if (! ConnectivityChecker::mayorReachable()) {
            $this->aplicarBackoff();
            return 1;
        }

        // Conectividad restablecida → limpiar backoff
        Cache::forget('sync:fallos_consecutivos');
        Cache::forget('sync:blocked_until');

        $mayorUrl   = rtrim(config('instance.mayor_url'), '/');
        $mayorToken = config('instance.mayor_token');

        $pendientes = SyncQueue::reintentable()->orderBy('id')->limit(200)->get();

        if ($pendientes->isEmpty()) {
            $this->info('No hay registros pendientes.');
            return 0;
        }

        $this->info("Enviando {$pendientes->count()} registros al Mayor...");

        $aceptados  = 0;
        $rechazados = 0;

        foreach ($pendientes->chunk(self::BATCH_SIZE) as $lote) {
            $payload = $lote->map(fn ($r) => [
                'tabla'     => $r->tabla,
                'uuid'      => $r->uuid,
                'accion'    => $r->accion,
                'datos'     => $r->datos,
                'origen_at' => $r->origen_at->toIso8601String(),
            ])->values()->all();

            try {
                $response = Http::withToken($mayorToken)
                    ->timeout(30)
                    ->post("{$mayorUrl}/api/sync/push", ['lote' => $payload]);

                if ($response->successful()) {
                    $body         = $response->json();
                    $uuidsAcept   = collect($body['aceptados']  ?? []);
                    $uuidsRechaz  = collect($body['rechazados'] ?? [])->pluck('uuid');

                    foreach ($lote as $registro) {
                        if ($uuidsAcept->contains($registro->uuid)) {
                            $registro->marcarEnviado();
                            $aceptados++;
                        } elseif ($uuidsRechaz->contains($registro->uuid)) {
                            $registro->registrarError('Rechazado por el Mayor');
                            $rechazados++;
                        }
                    }
                } else {
                    $error = "HTTP {$response->status()}: " . substr($response->body(), 0, 200);
                    foreach ($lote as $registro) {
                        $registro->registrarError($error);
                    }
                    $rechazados += $lote->count();
                }
            } catch (\Throwable $e) {
                $error = substr($e->getMessage(), 0, 200);
                foreach ($lote as $registro) {
                    $registro->registrarError($error);
                }
                $rechazados += $lote->count();
            }
        }

        $this->info("Push completo: {$aceptados} aceptados, {$rechazados} rechazados.");
        return $rechazados > 0 ? 1 : 0;
    }

    private function aplicarBackoff(): void
    {
        $fallos  = (int) Cache::get('sync:fallos_consecutivos', 0) + 1;
        $minutos = min(5 * (2 ** ($fallos - 1)), 60); // 5→10→20→40→60 min

        Cache::put('sync:fallos_consecutivos', $fallos, now()->addHours(2));
        Cache::put('sync:blocked_until', now()->addMinutes($minutos), now()->addHours(2));

        $this->warn("Mayor no alcanzable. Backoff por {$minutos} min (fallo #{$fallos}).");
    }
}
