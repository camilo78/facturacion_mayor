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

        // ── [14] FIX: watermark desalineado ──────────────────────────────────────
        // Si --force o si la BD del tenant está vacía, ignorar el watermark y hacer
        // pull completo. Evita que un watermark stale cause un incremental con 0 registros
        // en una BD que debería estar llena.
        $desde = $this->resolverDesde();

        $label = $desde ? "desde {$desde}" : 'sincronización completa';
        $this->info("Solicitando datos al Mayor ({$label})...");

        try {
            // ── 1. Catálogos ──────────────────────────────────────────────────────
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

            // ── 2. Usuarios ───────────────────────────────────────────────────────
            $userCount = $this->pullUsuarios($mayorUrl, $mayorToken, $desde);
            if ($userCount >= 0) {
                $this->info("Usuarios: {$userCount} sincronizados.");
            }

            // Guardar watermark solo tras pull exitoso
            Cache::put('sync:last_pull_at', $body['hasta'], now()->addDays(30));

            return 0;
        } catch (\Throwable $e) {
            $this->error("Excepción en sync:pull: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * [14] Determina el parámetro "desde" para el pull.
     *
     * Si --force o si las tablas del tenant están vacías, devuelve null (pull completo).
     * Si hay watermark stale con tablas vacías, lo borra y devuelve null.
     * Solo devuelve el watermark si las tablas tienen datos (pull incremental genuino).
     */
    private function resolverDesde(): ?string
    {
        if ($this->option('force')) {
            return null;
        }

        $watermark = Cache::get('sync:last_pull_at');
        if (! $watermark) {
            return null; // sin watermark → pull completo
        }

        // Si la BD del tenant está vacía a pesar de tener watermark, forzar pull completo
        if ($this->tenantVacio()) {
            $this->warn("BD del tenant vacía pero watermark presente ({$watermark}) — forzando pull completo.");
            Cache::forget('sync:last_pull_at');
            return null;
        }

        return $watermark;
    }

    /**
     * Verifica si las tablas principales del tenant están vacías.
     * Se llama con tenancy ya inicializado.
     */
    private function tenantVacio(): bool
    {
        try {
            return DB::table('productos')->count() === 0
                && DB::table('clientes')->count() === 0
                && DB::table('users')->count() === 0;
        } catch (\Throwable) {
            return true; // si no se pueden consultar, tratar como vacías
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
     * Los roles ya existen con permisos porque RolesPermisosSeeder corrió antes.
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

                // Garantizar que los roles existen (el seeder ya los crea con permisos;
                // firstOrCreate es seguro si por alguna razón el seeder no corrió)
                foreach ($roles as $roleName) {
                    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                }

                // Upsert con DB::table() — bypasa el cast 'hashed' de User::$casts
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

                // Sincronizar roles via Eloquent (no toca el password)
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
