<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermisosSeeder extends Seeder
{
    // Catálogo completo de permisos agrupados por sección
    public const PERMISOS = [
        'Facturación'       => ['facturas.emitir', 'facturas.ver', 'facturas.anular', 'facturas.exportar'],
        'Clientes'          => ['clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar'],
        'Productos'         => ['productos.ver', 'productos.crear', 'productos.editar', 'productos.eliminar'],
        'Impuestos'         => ['impuestos.ver', 'impuestos.editar'],
        'Configuración'     => ['establecimientos.gestionar', 'puntos-emision.gestionar', 'cai.gestionar', 'empresa.editar'],
        'Usuarios'          => ['usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar'],
        'Roles'             => ['roles.gestionar'],
        'Reportes'          => ['reportes.ventas', 'reportes.fiscales', 'reportes.exportar'],
        'Auditoría'         => ['auditoria.ver'],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear todos los permisos
        $todos = collect(self::PERMISOS)->flatten();
        foreach ($todos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        // ── Admin ────────────────────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions($todos->toArray());

        // ── Contador ─────────────────────────────────────────────────
        $contador = Role::firstOrCreate(['name' => 'Contador', 'guard_name' => 'web']);
        $contador->syncPermissions([
            'facturas.ver', 'facturas.exportar',
            'clientes.ver', 'productos.ver', 'impuestos.ver',
            'cai.gestionar',
            'empresa.editar',
            'reportes.ventas', 'reportes.fiscales', 'reportes.exportar',
            'auditoria.ver',
        ]);

        // ── Supervisor de Caja ────────────────────────────────────────
        $supervisor = Role::firstOrCreate(['name' => 'Supervisor de Caja', 'guard_name' => 'web']);
        $supervisor->syncPermissions([
            'facturas.emitir', 'facturas.ver', 'facturas.anular',
            'clientes.ver', 'clientes.crear', 'clientes.editar',
            'productos.ver',
            'reportes.ventas',
            'usuarios.ver',
        ]);

        // ── Cajero ────────────────────────────────────────────────────
        $cajero = Role::firstOrCreate(['name' => 'Cajero', 'guard_name' => 'web']);
        $cajero->syncPermissions([
            'facturas.emitir', 'facturas.ver',
            'clientes.ver', 'clientes.crear',
            'productos.ver',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
