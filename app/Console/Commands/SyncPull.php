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
use App\Models\User;
use App\Services\ConnectivityChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

class SyncPull extends Command
{
    protected $signature = 'sync:pull {--force : Descarga completa ignorando último timestamp}';
    protected $description = 'Descarga datos maestros y usuarios del Mayor y los aplica localmente';

    private const MODEL_MAP = [
        'establecimientos'   => Establecimiento::class,
        'puntos_emision'     => PuntoEmision::class,
        'cai_autorizaciones' => CaiAutorizacion::class,
        'impuestos'          => Impuesto::class,
        'clientes'           => Cliente::class,
        'productos'          => Producto::class,
    ];

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

        $label = $desde ? "desde {$desde}" : 'sincronización completa';
        $this->info("Solicitando datos al Mayor ({$label})...");

        try {
            // ── 1. Catálogos ─────────────────────────────────────────────────
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

            $totales = collect($body['totales'] ?? [])
                ->map(fn ($n, $t) => "{$t}:{$n}")
                ->implode(' ');
            $this->info("Catálogos: {$totales}");

            // ── 2. Usuarios ───────────────────────────────────────────────────
            $userCount = $this->pullUsuarios($mayorUrl, $mayorToken, $desde);
            if ($userCount >= 0) {
                $this->info("Usuarios: {$userCount} sincronizados.");
            }

            // Guardar timestamp para el próximo pull delta
            Cache::put('sync:last_pull_at', $body['hasta'], now()->addDays(30));

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

                    foreach ($strip as $key) {
                        unset($row[$key]);
                    }

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

    /**
     * Sincronizar usuarios desde el Mayor.
     *
     * Usa DB::table() para insertar el hash bcrypt sin pasar por el cast 'hashed'
     * de Eloquent (que re-hashearía el valor y rompería el login).
     * Los roles se sincronizan vía Eloquent después del upsert.
     *
     * @return int  cantidad de usuarios procesados, -1 en error
     */
    private function pullUsuarios(string $mayorUrl, string $mayorToken, ?string $desde): int
    {
        $params = [];
        if ($desde) {
            $params['desde'] = $desde;
        }

        $response = Http::withToken($mayorToken)
            ->timeout(30)
            ->get("{$mayorUrl}/api/sync/usuarios", $params);

        if (! $response->successful()) {
            $this->warn("Sync usuarios: HTTP {$response->status()} — omitiendo.");
            return -1;
        }

        $usuarios = $response->json('usuarios', []);
        $count    = 0;

        SyncQueue::suspender(function () use ($usuarios, &$count) {
            foreach ($usuarios as $userData) {
                $email    = $userData['email']    ?? null;
                $password = $userData['password'] ?? null;

                if (! $email || ! $password) {
                    continue;
                }

                $roles = $userData['roles'] ?? [];

                // 1. Garantizar que los roles existen en el tenant antes de asignarlos
                foreach ($roles as $roleName) {
                    Role::firstOrCreate(
                        ['name' => $roleName, 'guard_name' => 'web']
                    );
                }

                // 2. Upsert con DB::table() — bypasa el cast 'hashed' de User::$casts
                //    para evitar que el hash bcrypt sea re-hasheado (lo haría inutilizable)
                $exists = DB::table('users')->where('email', $email)->exists();

                $data = [
                    'name'              => $userData['name']              ?? $email,
                    'password'          => $password,                       // hash bcrypt raw
                    'email_verified_at' => $userData['email_verified_at'] ?? null,
                    'activo'            => $userData['activo']             ?? true,
                    'synced_at'         => now(),
                    'updated_at'        => now(),
                ];

                if (! $exists) {
                    $data['email']      = $email;
                    $data['uuid']       = $userData['uuid'] ?? (string) \Illuminate\Support\Str::uuid();
                    $data['origin']     = 'mayor';
                    $data['created_at'] = now();
                }

                DB::table('users')->updateOrInsert(['email' => $email], $data);

                // 3. Sincronizar roles via Eloquent (no toca el password)
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->syncRoles($roles);
                }

                $count++;
            }
        });

        return $count;
    }
}
