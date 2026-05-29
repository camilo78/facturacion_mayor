<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Establecimiento;
use App\Models\PuntoEmision;
use App\Models\Impuesto;

class EmpresaCrear extends Command
{
    protected $signature = 'empresa:crear';
    protected $description = 'Da de alta una empresa (tenant) con su base de datos y usuario administrador';

    public function handle(): int
    {
        $nombre          = $this->ask('Razón social');
        $rtn             = str_replace('-', '', (string) $this->ask('RTN (14 dígitos)'));
        $nombreComercial = $this->ask('Nombre comercial (enter para omitir)');
        $email           = $this->ask('Email del administrador');
        $password        = $this->secret('Contraseña del administrador');

        if (!preg_match('/^\d{14}$/', $rtn)) {
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
            'email'            => $email,
            'plan'             => 'basico',
            'estado'           => 'activo',
        ]);

        // Sembrar el usuario admin DENTRO de la base de la empresa
        $tenant->run(function () use ($email, $password) {
            // Usuario administrador
            User::create([
                'name'     => 'Administrador',
                'email'    => $email,
                'password' => Hash::make($password),
            ]);

            // Casa matriz (establecimiento 000 por defecto, editable)
            $matriz = Establecimiento::create([
                'codigo' => '000',
                'nombre' => 'Casa Matriz',
                'activo' => true,
            ]);

            // Caja inicial de la casa matriz (emisor por defecto: Mayor)
            PuntoEmision::create([
                'establecimiento_id' => $matriz->id,
                'codigo'             => '001',
                'nombre'             => 'Caja 1',
                'emisor_tipo'        => 'mayor',
                'activo'             => true,
            ]);

            // Impuestos por defecto (tasas editables)
            foreach ([
                ['codigo' => 'EXENTO', 'nombre' => 'Exento', 'tasa' => 0.00,  'es_default' => false],
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