<?php

namespace App\Console\Commands;

use App\Models\Establecimiento;
use App\Models\Impuesto;
use App\Models\PuntoEmision;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmpresaCrear extends Command
{
    protected $signature   = 'empresa:crear';
    protected $description = 'Da de alta una empresa (tenant) con su base de datos y usuario administrador';

    public function handle(): int
    {
        $nombre          = $this->ask('Razón social');
        $rtn             = str_replace('-', '', (string) $this->ask('RTN (14 dígitos)'));
        $nombreComercial = $this->ask('Nombre comercial (enter para omitir)');
        $direccion       = $this->ask('Dirección de la empresa (enter para omitir)');
        $telefono        = $this->ask('Teléfono de la empresa (enter para omitir)');
        $emailEmpresa    = $this->ask('Email de la empresa (facturación)');
        $email           = $this->ask('Email del administrador');
        $password        = $this->secret('Contraseña del administrador');

        if (! preg_match('/^\d{14}$/', $rtn)) {
            $this->error('El RTN debe tener 14 dígitos.');
            return self::FAILURE;
        }

        $id = Str::slug($nombre, '_') . '_' . strtolower(Str::random(6));

        $this->info('Creando empresa y provisionando su base de datos...');

        $tenant = Tenant::create([
            'id'               => $id,
            'nombre'           => $nombre,
            'nombre_comercial' => $nombreComercial ?: null,
            'rtn'              => $rtn,
            'email'            => $emailEmpresa ?: $email,
            'telefono'         => $telefono ?: null,
            'plan'             => 'basico',
            'estado'           => 'activo',
        ]);

        $tenant->run(function () use ($email, $password) {
            // 1. Roles y permisos
            (new RolesPermisosSeeder())->run();

            // 2. Usuario administrador
            $admin = User::create([
                'name'     => 'Administrador',
                'email'    => $email,
                'password' => Hash::make($password),
                'activo'   => true,
            ]);
            $admin->assignRole('Admin');

            // 3. Casa matriz
            $matriz = Establecimiento::create([
                'codigo'    => '000',
                'nombre'    => 'Casa Matriz',
                'direccion' => $direccion ?: null,
                'telefono'  => $telefono ?: null,
                'activo'    => true,
            ]);

            // 4. Caja inicial
            PuntoEmision::create([
                'establecimiento_id' => $matriz->id,
                'codigo'             => '001',
                'nombre'             => 'Caja 1',
                'emisor_tipo'        => 'mayor',
                'activo'             => true,
            ]);

            // 5. Impuestos por defecto
            foreach ([
                ['codigo' => 'EXENTO', 'nombre' => 'Exento',  'tasa' => 0.00,  'es_default' => false],
                ['codigo' => 'ISV15',  'nombre' => 'ISV 15%', 'tasa' => 15.00, 'es_default' => true],
                ['codigo' => 'ISV18',  'nombre' => 'ISV 18%', 'tasa' => 18.00, 'es_default' => false],
            ] as $imp) {
                Impuesto::create($imp);
            }
        });

        $this->newLine();
        $this->info("Empresa creada: {$tenant->nombre}");
        $this->line("  ID (tenant):   {$tenant->id}");
        $this->line("  Base de datos: {$tenant->tenancy_db_name}");
        $this->line("  Admin:         {$email}");

        return self::SUCCESS;
    }
}
