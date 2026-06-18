<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class InstanceToken extends Model
{
    use CentralConnection;
    protected $fillable = [
        'instance_id',
        'token_hash',
        'revocado',
        'ultimo_uso_at',
    ];

    protected $casts = [
        'revocado'      => 'boolean',
        'ultimo_uso_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function generate(string $instanceId): array
    {
        $plain = Str::random(64);
        $token = static::create([
            'instance_id' => $instanceId,
            'token_hash'  => hash('sha256', $plain),
        ]);

        return [$token, $plain];
    }

    public static function findByPlainToken(string $plain): ?static
    {
        return static::where('token_hash', hash('sha256', $plain))
            ->where('revocado', false)
            ->with('instance')
            ->first();
    }

    public function revocar(): void
    {
        $this->update(['revocado' => true]);
    }

    public function registrarUso(): void
    {
        $this->update(['ultimo_uso_at' => now()]);
    }
}
