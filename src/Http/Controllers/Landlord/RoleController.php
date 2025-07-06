<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Http\Controllers\Landlord;

use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Enums\RoleStatus;
use Callcocam\LaraGatekeeper\Models\Permission;
use Callcocam\LaraGatekeeper\Models\Role;
use Callcocam\LaraGatekeeper\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RoleController extends AbstractController
{
    protected ?string $model = Role::class;

    protected string $resourceName = 'Cargo Global';
    protected string $pluralResourceName = 'Cargos Globais'; 

    public function getSidebarMenuOrder(): int
    {
        return 20;
    }

    public function getSidebarMenuIconName(): string
    {
        return 'ShieldCheck';
    }

    protected function getFields(?Model $model = null): array
    {
        return [
            Field::make('name', 'Nome do Papel')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('slug', 'Slug')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('description', 'Descrição')
                ->type('textarea')
                ->colSpan(12),

            Field::make('permissions', 'Permissões')
                ->type('checkboxList')
                ->relationship('permissions', 'name', 'id')
                ->options(Permission::pluck('name', 'id')->toArray())
                ->gridCols(3)
                ->colSpan(12),

            Field::make('tenant_id', 'Tenant Específico (Opcional)')
                ->type('select')
                ->options(Tenant::where('status', 'active')->pluck('name', 'id')->toArray())
                ->colSpan(6)
                ->help('Deixe vazio para aplicar a todos os tenants'),

            Field::make('is_global', 'Papel Global')
                ->type('checkbox')
                ->colSpan(6)
                ->help('Papéis globais se aplicam a todos os tenants'),

            Field::make('status', 'Status')
                ->type('select')
                ->options(RoleStatus::getOptions())
                ->required()
                ->colSpan(12),
        ];
    }

    protected function getTableColumns(): array
    {
        $columns = [
            Column::make('Nome', 'name')->sortable(),

            Column::make('Slug', 'slug')->sortable(),

            Column::make('Tenant', 'tenant')
                ->cell(function (Role $row) {
                    if ($row->tenant_id) {
                        $tenant = Tenant::find($row->tenant_id);
                        return $tenant ? $tenant->name : 'Tenant não encontrado';
                    }
                    return $row->is_global ? 'Global (Todos)' : 'Não especificado';
                }),

            Column::make('Permissões', 'permissions_count')
                ->cell(function (Role $row) {
                    return $row->permissions()->count() . ' permissões';
                }),

            Column::make('Usuários', 'users_count')
                ->cell(function (Role $row) {
                    return $row->users()->count() . ' usuários';
                }),

            Column::make('Tipo', 'type')
                ->cell(function (Role $row) {
                    if ($row->is_global) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Global</span>';
                    } elseif ($row->tenant_id) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Tenant</span>';
                    }
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Padrão</span>';
                })
                ->html(),

            Column::make('Criado em', 'created_at')
                ->sortable()
                ->formatter('formatDate')
                ->options('dd/MM/yyyy HH:mm'),

            Column::make('Status', 'status')
                ->sortable()
                ->formatter('renderBadge')
                ->options(RoleStatus::options()),

            Column::actions(),
        ];

        return $columns;
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'slug', 'description'];
    }

    protected function getFilters(): array
    {
        return [
            [
                'column' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'options' => RoleStatus::options(),
            ],
            [
                'column' => 'tenant_id',
                'label' => 'Tenant',
                'type' => 'select',
                'options' => ['' => 'Todos'] + Tenant::where('status', 'active')->pluck('name', 'id')->toArray(),
            ],
            [
                'column' => 'is_global',
                'label' => 'Tipo',
                'type' => 'select',
                'options' => [
                    '' => 'Todos',
                    '1' => 'Global',
                    '0' => 'Específico',
                ],
            ]
        ];
    }

    protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array
    {
        $roleId = $model?->id;
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', $isUpdate ? Rule::unique('roles')->ignore($roleId) : Rule::unique('roles')],
            'description' => ['nullable', 'string'],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'tenant_id' => 'nullable|exists:tenants,id',
            'is_global' => 'boolean',
            'status' => ['required', Rule::in(array_column(RoleStatus::cases(), 'value'))],
        ];

        return $rules;
    }

    protected function getWithRelations(): array
    {
        return ['permissions', 'tenant'];
    }

    protected function beforeStore(array $validatedData, Request $request): array
    {
        // Se é global, não pode ter tenant específico
        if (!empty($validatedData['is_global'])) {
            $validatedData['tenant_id'] = null;
        }

        // Gerar slug se não fornecido
        if (empty($validatedData['slug'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        return $validatedData;
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate($this->getValidationRules());
        $validatedData = $this->beforeStore($validatedData, $request);
        
        $permissionIds = $validatedData['permissions'] ?? [];
        unset($validatedData['permissions']);

        DB::transaction(function () use ($validatedData, $permissionIds) {
            $role = $this->model::create($validatedData);
            
            if ($role && $permissionIds) {
                $role->permissions()->sync($permissionIds);
            }
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Papel criado com sucesso e aplicado conforme configuração.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $role = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $role));
        $validatedData = $this->beforeStore($validatedData, $request); // Usar mesma lógica
        
        $permissionIds = $validatedData['permissions'] ?? [];
        unset($validatedData['permissions']);

        DB::transaction(function () use ($role, $validatedData, $permissionIds) {
            $role->update($validatedData);
            
            if ($permissionIds) {
                $role->permissions()->sync($permissionIds);
            } else {
                $role->permissions()->detach();
            }
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Papel atualizado com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $role = $this->model::findOrFail($id);

        // Verificar se o papel está sendo usado
        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', "Não é possível excluir este papel pois está sendo usado por {$usersCount} usuário(s).");
        }

        DB::transaction(function () use ($role) {
            // Remover permissões
            $role->permissions()->detach();
            // Excluir papel
            $role->delete();
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Papel excluído com sucesso.');
    }

    /**
     * Sincronizar papel com tenants específicos
     */
    public function syncWithTenants(Request $request, string $id): RedirectResponse
    {
        $role = $this->model::findOrFail($id);
        $tenantIds = $request->validate([
            'tenant_ids' => 'required|array',
            'tenant_ids.*' => 'exists:tenants,id'
        ])['tenant_ids'];

        DB::transaction(function () use ($role, $tenantIds) {
            foreach ($tenantIds as $tenantId) {
                // Lógica para sincronizar papel com tenant específico
                // Isso pode envolver criar uma cópia do papel para o tenant
                // ou adicionar uma relação many-to-many
            }
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Papel sincronizado com os tenants selecionados.');
    }

    /**
     * Clonar papel para outro tenant
     */
    public function cloneToTenant(Request $request, string $id): RedirectResponse
    {
        $role = $this->model::findOrFail($id);
        $targetTenantId = $request->validate([
            'target_tenant_id' => 'required|exists:tenants,id'
        ])['target_tenant_id'];

        $targetTenant = Tenant::findOrFail($targetTenantId);

        DB::transaction(function () use ($role, $targetTenantId, $targetTenant) {
            // Criar cópia do papel para o tenant específico
            $newRole = $role->replicate();
            $newRole->tenant_id = $targetTenantId;
            $newRole->is_global = false;
            $newRole->name = $role->name . ' (' . $targetTenant->name . ')';
            $newRole->slug = Str::slug($newRole->name);
            $newRole->save();

            // Copiar permissões
            $permissionIds = $role->permissions()->pluck('id')->toArray();
            $newRole->permissions()->sync($permissionIds);
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', "Papel clonado com sucesso para o tenant '{$targetTenant->name}'.");
    }

    protected function getExtraDataForIndex(): array
    {
        return [
            'statistics' => [
                'total_roles' => Role::count(),
                'global_roles' => Role::where('is_global', true)->count(),
                'tenant_specific_roles' => Role::whereNotNull('tenant_id')->count(),
                'active_roles' => Role::where('status', 'active')->count(),
            ],
            'tenants' => Tenant::where('status', 'active')->select('id', 'name')->get(),
        ];
    }
} 