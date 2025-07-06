<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Http\Controllers\Landlord;

use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Enums\PermissionStatus;
use Callcocam\LaraGatekeeper\Models\Permission;
use Callcocam\LaraGatekeeper\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PermissionController extends AbstractController
{
    protected ?string $model = Permission::class;

    protected string $resourceName = 'Permissão Global';
    protected string $pluralResourceName = 'Permissões Globais'; 

    public function getSidebarMenuOrder(): int
    {
        return 25;
    }

    public function getSidebarMenuIconName(): string
    {
        return 'KeyRound';
    }

    protected function getFields(?Model $model = null): array
    {
        return [
            Field::make('name', 'Nome da Permissão')
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

            Field::make('category', 'Categoria')
                ->type('select')
                ->options([
                    'system' => 'Sistema',
                    'user' => 'Usuários',
                    'tenant' => 'Tenants',
                    'role' => 'Papéis',
                    'permission' => 'Permissões',
                    'content' => 'Conteúdo',
                    'financial' => 'Financeiro',
                    'report' => 'Relatórios',
                    'custom' => 'Personalizado',
                ])
                ->colSpan(6),

            Field::make('resource', 'Recurso')
                ->type('text')
                ->help('Ex: users, tenants, roles, posts')
                ->colSpan(6),

            Field::make('action', 'Ação')
                ->type('select')
                ->options([
                    'create' => 'Criar',
                    'read' => 'Visualizar',
                    'update' => 'Editar',
                    'delete' => 'Excluir',
                    'manage' => 'Gerenciar',
                    'export' => 'Exportar',
                    'import' => 'Importar',
                    'approve' => 'Aprovar',
                    'publish' => 'Publicar',
                    'custom' => 'Personalizado',
                ])
                ->colSpan(6),

            Field::make('tenant_id', 'Tenant Específico (Opcional)')
                ->type('select')
                ->options(Tenant::where('status', 'active')->pluck('name', 'id')->toArray())
                ->colSpan(6)
                ->help('Deixe vazio para aplicar a todos os tenants'),

            Field::make('is_global', 'Permissão Global')
                ->type('checkbox')
                ->colSpan(6)
                ->help('Permissões globais se aplicam a todos os tenants'),

            Field::make('is_system', 'Permissão do Sistema')
                ->type('checkbox')
                ->colSpan(6)
                ->help('Permissões do sistema não podem ser editadas pelos tenants'),

            Field::make('status', 'Status')
                ->type('select')
                ->options(PermissionStatus::getOptions())
                ->required()
                ->colSpan(12),
        ];
    }

    protected function getTableColumns(): array
    {
        $columns = [
            Column::make('Nome', 'name')->sortable(),

            Column::make('Slug', 'slug')->sortable(),

            Column::make('Categoria', 'category')
                ->cell(function (Permission $row) {
                    $categories = [
                        'system' => 'Sistema',
                        'user' => 'Usuários',
                        'tenant' => 'Tenants',
                        'role' => 'Papéis',
                        'permission' => 'Permissões',
                        'content' => 'Conteúdo',
                        'financial' => 'Financeiro',
                        'report' => 'Relatórios',
                        'custom' => 'Personalizado',
                    ];
                    return $categories[$row->category] ?? $row->category;
                }),

            Column::make('Recurso/Ação', 'resource_action')
                ->cell(function (Permission $row) {
                    return ($row->resource ? $row->resource . '.' : '') . ($row->action ?? 'N/A');
                }),

            Column::make('Tenant', 'tenant')
                ->cell(function (Permission $row) {
                    if ($row->tenant_id) {
                        $tenant = Tenant::find($row->tenant_id);
                        return $tenant ? $tenant->name : 'Tenant não encontrado';
                    }
                    return $row->is_global ? 'Global (Todos)' : 'Não especificado';
                }),

            Column::make('Tipo', 'type')
                ->cell(function (Permission $row) {
                    $badges = [];
                    
                    if ($row->is_system) {
                        $badges[] = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 mr-1">Sistema</span>';
                    }
                    
                    if ($row->is_global) {
                        $badges[] = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mr-1">Global</span>';
                    } elseif ($row->tenant_id) {
                        $badges[] = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1">Tenant</span>';
                    }
                    
                    return implode('', $badges) ?: '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Padrão</span>';
                })
                ->html(),

            Column::make('Papéis', 'roles_count')
                ->cell(function (Permission $row) {
                    return $row->roles()->count() . ' papéis';
                }),

            Column::make('Criado em', 'created_at')
                ->sortable()
                ->formatter('formatDate')
                ->options('dd/MM/yyyy HH:mm'),

            Column::make('Status', 'status')
                ->sortable()
                ->formatter('renderBadge')
                ->options(PermissionStatus::variantOptions()),

            Column::actions(),
        ];

        return $columns;
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'slug', 'description', 'resource', 'action'];
    }

    protected function getFilters(): array
    {
        return [
            [
                'column' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'options' => PermissionStatus::options(),
            ],
            [
                'column' => 'category',
                'label' => 'Categoria',
                'type' => 'select',
                'options' => [
                    '' => 'Todas',
                    'system' => 'Sistema',
                    'user' => 'Usuários',
                    'tenant' => 'Tenants',
                    'role' => 'Papéis',
                    'permission' => 'Permissões',
                    'content' => 'Conteúdo',
                    'financial' => 'Financeiro',
                    'report' => 'Relatórios',
                    'custom' => 'Personalizado',
                ],
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
            ],
            [
                'column' => 'is_system',
                'label' => 'Sistema',
                'type' => 'select',
                'options' => [
                    '' => 'Todos',
                    '1' => 'Sistema',
                    '0' => 'Personalizado',
                ],
            ]
        ];
    }

    protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array
    {
        $permissionId = $model?->id;
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $isUpdate ? Rule::unique('permissions')->ignore($permissionId) : Rule::unique('permissions')],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
            'resource' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:50'],
            'tenant_id' => 'nullable|exists:tenants,id',
            'is_global' => 'boolean',
            'is_system' => 'boolean',
            'status' => ['required', Rule::in(array_column(PermissionStatus::cases(), 'value'))],
        ];

        return $rules;
    }

    protected function getWithRelations(): array
    {
        return ['tenant', 'roles'];
    }

    protected function beforeStore(array $validatedData, Request $request): array
    {
        // Se é global, não pode ter tenant específico
        if (!empty($validatedData['is_global'])) {
            $validatedData['tenant_id'] = null;
        }

        // Gerar slug baseado no recurso e ação se não fornecido
        if (empty($validatedData['slug'])) {
            if ($validatedData['resource'] && $validatedData['action']) {
                $validatedData['slug'] = $validatedData['resource'] . '.' . $validatedData['action'];
            } else {
                $validatedData['slug'] = Str::slug($validatedData['name']);
            }
        }

        return $validatedData;
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate($this->getValidationRules());
        $validatedData = $this->beforeStore($validatedData, $request);

        $permission = $this->model::create($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Permissão criada com sucesso e aplicada conforme configuração.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $permission = $this->model::findOrFail($id);
        
        // Verificar se é permissão do sistema
        if ($permission->is_system) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Permissões do sistema não podem ser editadas.');
        }

        $validatedData = $request->validate($this->getValidationRules(true, $permission));
        $validatedData = $this->beforeStore($validatedData, $request);

        $permission->update($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Permissão atualizada com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $permission = $this->model::findOrFail($id);

        // Verificar se é permissão do sistema
        if ($permission->is_system) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Permissões do sistema não podem ser excluídas.');
        }

        // Verificar se a permissão está sendo usada
        $rolesCount = $permission->roles()->count();
        if ($rolesCount > 0) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', "Não é possível excluir esta permissão pois está sendo usada por {$rolesCount} papel(éis).");
        }

        $permission->delete();

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Permissão excluída com sucesso.');
    }

    /**
     * Gerar permissões padrão para um recurso
     */
    public function generateResourcePermissions(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'resource' => 'required|string|max:100',
            'category' => 'required|string|max:50',
            'tenant_id' => 'nullable|exists:tenants,id',
            'is_global' => 'boolean',
        ]);

        $resource = $validatedData['resource'];
        $category = $validatedData['category'];
        $tenantId = $validatedData['tenant_id'] ?? null;
        $isGlobal = $validatedData['is_global'] ?? false;

        $actions = ['create', 'read', 'update', 'delete', 'manage'];
        $createdCount = 0;

        DB::transaction(function () use ($resource, $category, $tenantId, $isGlobal, $actions, &$createdCount) {
            foreach ($actions as $action) {
                $slug = "{$resource}.{$action}";
                
                // Verificar se já existe
                $exists = Permission::where('slug', $slug)
                    ->where('tenant_id', $tenantId)
                    ->exists();

                if (!$exists) {
                    Permission::create([
                        'name' => ucfirst($action) . ' ' . ucfirst($resource),
                        'slug' => $slug,
                        'description' => "Permissão para {$action} {$resource}",
                        'category' => $category,
                        'resource' => $resource,
                        'action' => $action,
                        'tenant_id' => $isGlobal ? null : $tenantId,
                        'is_global' => $isGlobal,
                        'is_system' => false,
                        'status' => 'active',
                    ]);
                    $createdCount++;
                }
            }
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', "Geradas {$createdCount} permissões para o recurso '{$resource}'.");
    }

    /**
     * Clonar permissão para outro tenant
     */
    public function cloneToTenant(Request $request, string $id): RedirectResponse
    {
        $permission = $this->model::findOrFail($id);
        $targetTenantId = $request->validate([
            'target_tenant_id' => 'required|exists:tenants,id'
        ])['target_tenant_id'];

        $targetTenant = Tenant::findOrFail($targetTenantId);

        // Verificar se já existe
        $exists = Permission::where('slug', $permission->slug)
            ->where('tenant_id', $targetTenantId)
            ->exists();

        if ($exists) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', "Permissão '{$permission->slug}' já existe para o tenant '{$targetTenant->name}'.");
        }

        // Criar cópia
        $newPermission = $permission->replicate();
        $newPermission->tenant_id = $targetTenantId;
        $newPermission->is_global = false;
        $newPermission->name = $permission->name . ' (' . $targetTenant->name . ')';
        $newPermission->save();

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', "Permissão clonada com sucesso para o tenant '{$targetTenant->name}'.");
    }

    protected function getExtraDataForIndex(): array
    {
        return [
            'statistics' => [
                'total_permissions' => Permission::count(),
                'global_permissions' => Permission::where('is_global', true)->count(),
                'system_permissions' => Permission::where('is_system', true)->count(),
                'tenant_specific_permissions' => Permission::whereNotNull('tenant_id')->count(),
                'active_permissions' => Permission::where('status', 'active')->count(),
                'by_category' => Permission::selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->pluck('count', 'category')
                    ->toArray(),
            ],
            'tenants' => Tenant::where('status', 'active')->select('id', 'name')->get(),
        ];
    }
} 