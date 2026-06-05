<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Usuarios</h1>
            <p class="text-sm text-gray-500 mt-0.5">Cuentas de acceso de la empresa</p>
        </div>
        @can('usuarios.crear')
        <a href="{{ route('tenant.usuario.crear', tenant('id')) }}" class="btn-primary">
            <x-icon name="plus" class="w-4 h-4"/>
            Nuevo usuario
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card p-4">
        <div class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[200px] max-w-sm">
                <x-icon name="magnifying-glass" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"/>
                <input wire:model.live.debounce.300ms="busqueda"
                       type="text" placeholder="Nombre o email…"
                       class="form-input pl-9">
            </div>
            <select wire:model.live="filtroRol" class="form-select w-auto">
                <option value="">Todos los roles</option>
                @foreach($roles as $rol)
                    <option value="{{ $rol->name }}">{{ $rol->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="filtroActivo" class="form-select w-auto">
                <option value="">Todos los estados</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        @if($usuarios->isEmpty())
            <x-ui.empty-state icon="users" title="Sin usuarios"
                description="No hay usuarios que coincidan con los filtros."/>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-primary-600">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Nombre</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Email</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Rol</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide hidden md:table-cell">Último acceso</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Estado</th>
                        <th class="px-5 py-3 w-20"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($usuarios as $u)
                    <tr class="hover:bg-primary-50/60 transition-colors {{ !$u->activo ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 shrink-0 rounded-full bg-primary-100 text-primary-700 text-xs font-bold flex items-center justify-center">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <span class="font-medium text-gray-900">{{ $u->name }}</span>
                                @if($u->id === auth()->id())
                                    <span class="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">Tú</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $u->email }}</td>
                        <td class="px-5 py-3">
                            @if($rol = $u->roles->first())
                                @php
                                    $rolColor = match($rol->name) {
                                        'Admin'             => 'blue',
                                        'Contador'          => 'purple',
                                        'Supervisor de Caja'=> 'amber',
                                        'Cajero'            => 'gray',
                                        default             => 'gray',
                                    };
                                @endphp
                                <x-ui.badge :color="$rolColor">{{ $rol->name }}</x-ui.badge>
                            @else
                                <span class="text-xs text-gray-300">Sin rol</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 hidden md:table-cell text-xs">
                            {{ $u->ultimo_login ? $u->ultimo_login->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td class="px-5 py-3">
                            @can('usuarios.editar')
                            <button wire:click="toggleActivo({{ $u->id }})"
                                    wire:loading.attr="disabled"
                                    title="{{ $u->activo ? 'Desactivar' : 'Activar' }}">
                                <x-ui.badge :color="$u->activo ? 'green' : 'gray'">
                                    {{ $u->activo ? 'Activo' : 'Inactivo' }}
                                </x-ui.badge>
                            </button>
                            @else
                            <x-ui.badge :color="$u->activo ? 'green' : 'gray'">
                                {{ $u->activo ? 'Activo' : 'Inactivo' }}
                            </x-ui.badge>
                            @endcan
                        </td>
                        <td class="px-5 py-3 text-right">
                            @can('usuarios.editar')
                            <a href="{{ route('tenant.usuario.editar', ['tenantId' => tenant('id'), 'userId' => $u->id]) }}"
                               class="btn-ghost btn-sm">
                                <x-icon name="pencil" class="w-3.5 h-3.5"/>
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($usuarios->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
            <span class="text-xs">
                Mostrando {{ $usuarios->firstItem() }}–{{ $usuarios->lastItem() }} de {{ $usuarios->total() }}
            </span>
            {{ $usuarios->links() }}
        </div>
        @else
        <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">
            {{ $usuarios->total() }} usuario(s)
        </div>
        @endif
        @endif
    </div>

</div>
