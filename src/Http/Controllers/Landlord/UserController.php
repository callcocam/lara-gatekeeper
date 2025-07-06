<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Http\Controllers\Landlord;

use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Models\Auth\User;
use Callcocam\LaraGatekeeper\Models\Role;
use Callcocam\LaraGatekeeper\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserController extends AbstractController
{
    protected ?string $model = User::class;
    
    protected string $resourceName = 'Usuário Global';
    protected string $pluralResourceName = 'Usuários Globais';

    public function getSidebarMenuOrder(): int
    {
        return 10;
    }

    public function getSidebarMenuIconName(): string
    {
        return 'Users';
    }

    protected function getFields(?Model $model = null): array
    {
        $isUpdate = $model && $model->exists;

        return [
            Field::make('name', 'Nome')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('email', 'E-mail')
                ->type('email')
                ->required()
                ->colSpan(6),

            Field::make('password', $isUpdate ? 'Nova Senha' : 'Senha')
                ->type('password')
                ->required(!$isUpdate)
                ->colSpan(6),

            Field::make('password_confirmation', 'Confirmar Senha')
                ->type('password')
                ->required(!$isUpdate)
                ->colSpan(6),

            Field::make('avatar', $isUpdate ? 'Alterar Avatar' : 'Avatar')
                ->type('filepond')
                ->accept('image/*')
                ->colSpan(12),

            Field::make('tenant_id', 'Tenant Principal')
                ->type('select')
                ->options(Tenant::where('status', 'active')->pluck('name', 'id')->toArray())
                ->colSpan(6)
                ->help('Tenant principal do usuário'),

            Field::make('is_landlord', 'É Landlord')
                ->type('checkbox')
                ->colSpan(6)
                ->help('Usuários landlord podem gerenciar todos os tenants'),

            Field::make('roles', 'Funções Globais')
                ->type('select')
                ->multiple()
                ->options(Role::where('is_global', true)->orWhereNull('tenant_id')->pluck('name', 'id')->toArray())
                ->colSpan(6),

            Field::make('tenant_roles', 'Funções por Tenant')
                ->type('json')
                ->colSpan(6)
                ->help('Configurar funções específicas por tenant'),

            Field::make('additional_tenants', 'Tenants Adicionais')
                ->type('select')
                ->multiple()
                ->options(Tenant::where('status', 'active')->pluck('name', 'id')->toArray())
                ->colSpan(12)
                ->help('Tenants adicionais que o usuário pode acessar'),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Column::make('Avatar')
                ->id('avatar')
                ->accessorKey(null)
                ->hideable()
                ->html()
                ->cell(function (User $row) {
                    $url = $row->avatar ? Storage::disk(config('filesystems.default'))->url($row->avatar) : null;
                    return $url ? '<img src="' . $url . '" alt="Avatar" class="h-8 w-8 rounded-full object-cover">' : 
                                 '<div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-medium">' . 
                                 strtoupper(substr($row->name, 0, 2)) . '</div>';
                }),

            Column::make('Nome', 'name')->sortable(),
            
            Column::make('E-mail', 'email')->sortable(),

            Column::make('Tenant Principal', 'tenant')
                ->cell(function (User $row) {
                    if ($row->tenant_id) {
                        $tenant = Tenant::find($row->tenant_id);
                        return $tenant ? $tenant->name : 'Tenant não encontrado';
                    }
                    return 'Nenhum';
                }),

            Column::make('Tipo', 'user_type')
                ->cell(function (User $row) {
                    $badges = [];
                    
                    if ($row->is_landlord) {
                        $badges[] = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mr-1">Landlord</span>';
                    }
                    
                    if ($row->hasRole('super-admin')) {
                        $badges[] = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 mr-1">Super Admin</span>';
                    }
                    
                    $tenantsCount = $row->tenants ? $row->tenants()->count() : 0;
                    if ($tenantsCount > 1) {
                        $badges[] = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1">Multi-Tenant</span>';
                    }
                    
                    return implode('', $badges) ?: '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Padrão</span>';
                })
                ->html(),
            
            Column::make('Funções Globais', 'global_roles')
                ->cell(function (User $row) {
                    $globalRoles = $row->roles()->where('is_global', true)->orWhereNull('tenant_id')->pluck('name');
                    return $globalRoles->count() > 0 ? $globalRoles->join(', ') : 'Nenhuma';
                }),

            Column::make('Tenants', 'tenants_count')
                ->cell(function (User $row) {
                    $count = 1; // Tenant principal
                    if (method_exists($row, 'tenants')) {
                        $count = $row->tenants()->count();
                    }
                    return $count . ' tenant(s)';
                }),

            Column::make('Último Login', 'last_login_at')
                ->sortable()
                ->formatter('formatDate')
                ->options('dd/MM/yyyy HH:mm'),

            Column::make('Criado em', 'created_at')
                ->sortable()
                ->formatter('formatDate')
                ->options('dd/MM/yyyy HH:mm'),

            Column::actions(),
        ];
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'email'];
    }

    protected function getFilters(): array
    {
        return [
            [
                'column' => 'tenant_id',
                'label' => 'Tenant Principal',
                'type' => 'select',
                'options' => ['' => 'Todos'] + Tenant::where('status', 'active')->pluck('name', 'id')->toArray(),
            ],
            [
                'column' => 'is_landlord',
                'label' => 'Tipo',
                'type' => 'select',
                'options' => [
                    '' => 'Todos',
                    '1' => 'Landlord',
                    '0' => 'Usuário Regular',
                ],
            ],
            [
                'column' => 'roles',
                'label' => 'Função Global',
                'type' => 'select',
                'options' => Role::where('is_global', true)->orWhereNull('tenant_id')->pluck('name', 'id')->toArray(),
            ]
        ];
    }

    protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array
    {
        $userId = $model?->id;
        
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'avatar' => ['nullable'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'is_landlord' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'additional_tenants' => ['nullable', 'array'],
            'additional_tenants.*' => ['exists:tenants,id'],
        ];

        if (!$isUpdate) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    protected function getWithRelations(): array
    {
        return ['roles', 'tenant', 'tenants'];
    }

    protected function beforeStore(array $validatedData, Request $request): array
    {
        // Processar avatar
        if (isset($validatedData['avatar']) && $validatedData['avatar']) {
            $validatedData['avatar'] = $this->moveTemporaryFile($validatedData['avatar'], 'users/avatars');
        } else {
            unset($validatedData['avatar']);
        }

        // Hash da senha
        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        return $validatedData;
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate($this->getValidationRules());
        $validatedData = $this->beforeStore($validatedData, $request);
        
        $roles = $validatedData['roles'] ?? [];
        $additionalTenants = $validatedData['additional_tenants'] ?? [];
        
        unset($validatedData['roles'], $validatedData['additional_tenants']);

        DB::transaction(function () use ($validatedData, $roles, $additionalTenants) {
            $user = $this->model::create($validatedData);
            
            // Sincronizar funções globais
            if (!empty($roles)) {
                $user->roles()->sync($roles);
            }

            // Configurar tenants adicionais
            if (!empty($additionalTenants) && method_exists($user, 'tenants')) {
                $tenantsToSync = $additionalTenants;
                
                // Incluir tenant principal se especificado
                if ($validatedData['tenant_id'] && !in_array($validatedData['tenant_id'], $tenantsToSync)) {
                    $tenantsToSync[] = $validatedData['tenant_id'];
                }
                
                $user->tenants()->sync($tenantsToSync);
            }
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Usuário criado com sucesso com acesso configurado aos tenants.');
    }

    protected function beforeUpdate(array $validatedData, Request $request, ?Model $modelInstance = null): array
    {
        // Processar avatar
        if ($request->filled('avatar')) {
            if ($validatedData['avatar']) {
                $newPath = $this->moveTemporaryFile($validatedData['avatar'], 'users/avatars');
                
                // Remover avatar anterior
                if ($modelInstance->avatar && $newPath !== $modelInstance->avatar) {
                    Storage::disk(config('filesystems.default'))->delete($modelInstance->avatar);
                }
                
                $validatedData['avatar'] = $newPath;
            }
        } else {
            unset($validatedData['avatar']);
        }

        // Hash da senha se fornecida
        if (isset($validatedData['password']) && !empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        return $validatedData;
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $user = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $user));
        $validatedData = $this->beforeUpdate($validatedData, $request, $user);
        
        $roles = $validatedData['roles'] ?? [];
        $additionalTenants = $validatedData['additional_tenants'] ?? [];
        
        unset($validatedData['roles'], $validatedData['additional_tenants']);

        DB::transaction(function () use ($user, $validatedData, $roles, $additionalTenants) {
            $user->update($validatedData);
            
            // Sincronizar funções globais
            if (!empty($roles)) {
                $user->roles()->sync($roles);
            } else {
                $user->roles()->detach();
            }

            // Configurar tenants adicionais
            if (method_exists($user, 'tenants')) {
                $tenantsToSync = $additionalTenants;
                
                // Incluir tenant principal se especificado
                if ($validatedData['tenant_id'] && !in_array($validatedData['tenant_id'], $tenantsToSync)) {
                    $tenantsToSync[] = $validatedData['tenant_id'];
                }
                
                $user->tenants()->sync($tenantsToSync);
            }
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $user = $this->model::findOrFail($id);

        // Verificar se não é o próprio usuário logado
        if (auth()->id() == $user->id) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Você não pode excluir sua própria conta.');
        }

        // Verificar se é super admin
        if ($user->hasRole('super-admin')) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Usuários super admin não podem ser excluídos.');
        }

        DB::transaction(function () use ($user) {
            // Remover avatar se existir
            if ($user->avatar) {
                Storage::disk(config('filesystems.default'))->delete($user->avatar);
            }
            
            // Remover relacionamentos
            $user->roles()->detach();
            if (method_exists($user, 'tenants')) {
                $user->tenants()->detach();
            }
            
            // Excluir usuário
            $user->delete();
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Usuário excluído com sucesso.');
    }

    /**
     * Impersonar usuário
     */
    public function impersonate(string $id): RedirectResponse
    {
        $user = $this->model::findOrFail($id);

        // Verificar se pode impersonar
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->is_landlord) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Você não tem permissão para impersonar usuários.');
        }

        // Não pode impersonar super admin
        if ($user->hasRole('super-admin')) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Não é possível impersonar usuários super admin.');
        }

        // Salvar usuário original na sessão
        session(['impersonating_user_id' => auth()->id()]);
        
        // Fazer login como o usuário
        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('success', "Você está agora impersonando {$user->name}.");
    }

    /**
     * Parar impersonation
     */
    public function stopImpersonation(): RedirectResponse
    {
        $originalUserId = session('impersonating_user_id');
        
        if (!$originalUserId) {
            return redirect()->route('dashboard')
                ->with('error', 'Não há impersonation ativa.');
        }

        $originalUser = $this->model::findOrFail($originalUserId);
        
        // Voltar para o usuário original
        auth()->login($originalUser);
        session()->forget('impersonating_user_id');

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Impersonation finalizada com sucesso.');
    }

    protected function getExtraDataForIndex(): array
    {
        return [
            'statistics' => [
                'total_users' => User::count(),
                'landlord_users' => User::where('is_landlord', true)->count(),
                'multi_tenant_users' => User::whereHas('tenants', function($q) { 
                    $q->havingRaw('COUNT(*) > 1'); 
                })->count(),
                'active_sessions' => User::whereNotNull('last_login_at')
                    ->where('last_login_at', '>', now()->subDays(7))
                    ->count(),
                'by_tenant' => Tenant::withCount('users')->get()->pluck('users_count', 'name')->toArray(),
            ],
            'tenants' => Tenant::where('status', 'active')->select('id', 'name')->get(),
            'is_impersonating' => session()->has('impersonating_user_id'),
        ];
    }
} 