<?php

namespace App\Console\Commands;

use App\Models\Instance;
use App\Models\InstanceToken;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstanceRegistrar extends Command
{
    protected $signature = 'instance:registrar
                            {--tipo= : Tipo de instancia: mayor o auxiliar}
                            {--tenant= : ID del tenant (solo para auxiliar)}
                            {--establecimiento= : ID del establecimiento (opcional, solo para auxiliar)}
                            {--label= : Nombre descriptivo de la instancia}';

    protected $description = 'Registra una instancia (Mayor o Auxiliar) y genera su token de sincronización';

    public function handle(): int
    {
        $tipo = $this->option('tipo') ?? $this->choice('Tipo de instancia', ['mayor', 'auxiliar'], 0);

        if (! in_array($tipo, ['mayor', 'auxiliar'])) {
            $this->error('Tipo inválido. Usa: mayor | auxiliar');
            return self::FAILURE;
        }

        // ── Mayor ────────────────────────────────────────────────────────────
        if ($tipo === 'mayor') {
            return $this->registrarMayor();
        }

        // ── Auxiliar ─────────────────────────────────────────────────────────
        return $this->registrarAuxiliar();
    }

    private function registrarMayor(): int
    {
        $uuid = config('instance.uuid');

        if (! $uuid) {
            $this->error('INSTANCE_UUID no está configurado en .env');
            return self::FAILURE;
        }

        if (Instance::find($uuid)) {
            $this->warn("El Mayor ya está registrado (UUID: {$uuid})");
            return self::SUCCESS;
        }

        $label = $this->option('label')
            ?? $this->ask('Nombre descriptivo del Mayor', config('instance.label', 'Mayor'));

        Instance::create([
            'id'    => $uuid,
            'tipo'  => 'mayor',
            'label' => $label,
        ]);

        $this->info('');
        $this->info('  ✅  Mayor registrado correctamente.');
        $this->table(['Campo', 'Valor'], [
            ['UUID',  $uuid],
            ['Label', $label],
            ['Tipo',  'mayor'],
        ]);

        return self::SUCCESS;
    }

    private function registrarAuxiliar(): int
    {
        // Tenant
        $tenantId = $this->option('tenant') ?? $this->anticipate(
            '¿A qué empresa pertenece este Auxiliar? (ID del tenant)',
            Tenant::pluck('id')->all()
        );

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            $this->error("Tenant '{$tenantId}' no existe.");
            return self::FAILURE;
        }

        // Establecimiento (opcional)
        $establecimientoId = $this->option('establecimiento');

        // Label
        $label = $this->option('label')
            ?? $this->ask('Nombre descriptivo del Auxiliar', "Auxiliar — {$tenant->nombre}");

        // UUID nuevo para este Auxiliar
        $uuid = (string) Str::uuid();

        $instance = Instance::create([
            'id'                 => $uuid,
            'tipo'               => 'auxiliar',
            'tenant_id'          => $tenantId,
            'establecimiento_id' => $establecimientoId ?: null,
            'label'              => $label,
        ]);

        [$token, $plain] = InstanceToken::generate($uuid);

        $this->info('');
        $this->info('  ✅  Auxiliar registrado correctamente.');
        $this->table(['Campo', 'Valor'], [
            ['UUID',           $uuid],
            ['Label',          $label],
            ['Empresa',        "{$tenant->nombre} ({$tenantId})"],
            ['Establecimiento', $establecimientoId ?? '(ninguno)'],
        ]);

        $this->newLine();
        $this->warn('  ⚠️  Guarda este token — no se volverá a mostrar:');
        $this->newLine();
        $this->line("  <fg=yellow;options=bold>  {$plain}  </>");
        $this->newLine();
        $this->comment('  Agrega estas variables al .env del Auxiliar:');
        $this->line("  INSTANCE_MODE=auxiliar");
        $this->line("  INSTANCE_UUID={$uuid}");
        $this->line("  INSTANCE_LABEL=\"{$label}\"");
        $this->line("  MAYOR_SYNC_URL=" . rtrim(config('app.url'), '/'));
        $this->line("  MAYOR_SYNC_TOKEN={$plain}");
        $this->newLine();

        return self::SUCCESS;
    }
}
