<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Http\Controllers\Landlord;

use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Enums\TenantStatus;
use Callcocam\LaraGatekeeper\Models\Tenant;
use Callcocam\LaraGatekeeper\Models\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TenantController extends AbstractController
{
    protected ?string $model = Tenant::class;
    
    protected string $resourceName = 'Tenant';
    protected string $pluralResourceName = 'Tenants'; 

    public function getSidebarMenuOrder(): int
    {
        return 5;
    }

    public function getSidebarMenuIconName(): string
    {
        return 'Building2';
    }
 
    protected function getFields(?Model $model = null): array
    {
        $isUpdate = $model && $model->exists;

        return [
            Field::make('name', 'Nome do Tenant')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('slug', 'Slug')
                ->type('text')
                ->required()
                ->colSpan(6)
                ->help('Usado para URLs e identificação única'),

            Field::make('domain', 'Domínio')
                ->type('text')
                ->colSpan(6)
                ->help('Domínio personalizado (opcional)'),

            Field::make('email', 'E-mail Principal')
                ->type('email')
                ->required()
                ->colSpan(6),

            Field::make('phone', 'Telefone')
                ->type('text')
                ->colSpan(6),

            Field::make('description', 'Descrição')
                ->type('textarea')
                ->colSpan(12),

            Field::make('logo', $isUpdate ? 'Alterar Logo' : 'Logo')
                ->type('filepond')
                ->accept('image/*')
                ->colSpan(6),

            Field::make('plan', 'Plano')
                ->type('select')
                ->options([
                    'free' => 'Gratuito',
                    'basic' => 'Básico',
                    'premium' => 'Premium',
                    'enterprise' => 'Enterprise',
                ])
                ->colSpan(6),

            Field::make('max_users', 'Máximo de Usuários')
                ->type('number')
                ->min(1)
                ->colSpan(6)
                ->help('Limite de usuários para este tenant'),

            Field::make('max_storage_mb', 'Máximo de Armazenamento (MB)')
                ->type('number')
                ->min(100)
                ->colSpan(6)
                ->help('Limite de armazenamento em MB'),

            Field::make('expires_at', 'Data de Expiração')
                ->type('date')
                ->colSpan(6)
                ->help('Deixe vazio para nunca expirar'),

            Field::make('custom_settings', 'Configurações Personalizadas')
                ->type('json')
                ->colSpan(12)
                ->help('Configurações específicas do tenant em formato JSON'),

            Field::make('status', 'Status')
                ->type('select')
                ->options(TenantStatus::getOptions())
                ->required()
                ->colSpan(12),
        ];
    }

    protected function getTableColumns(): array
    {
        $columns = [
            Column::make('Logo')
                ->id('logo')
                ->accessorKey(null)
                ->hideable()
                ->html()
                ->cell(function (Tenant $row) {
                    $url = $row->logo ? Storage::disk(config('filesystems.default'))->url($row->logo) : null;
                    return $url ? '<img src="' . $url . '" alt="Logo" class="h-8 w-8 rounded object-cover">' : 
                                 '<div class="h-8 w-8 rounded bg-gray-300 flex items-center justify-center text-xs font-medium">' . 
                                 strtoupper(substr($row->name, 0, 2)) . '</div>';
                }),

            Column::make('Nome', 'name')->sortable(),

            Column::make('Slug', 'slug')->sortable(),

            Column::make('Domínio', 'domain')
                ->cell(function (Tenant $row) {
                    return $row->domain ?: 'Não configurado';
                }),

            Column::make('E-mail', 'email')->sortable(),

            Column::make('Plano', 'plan')
                ->cell(function (Tenant $row) {
                    $plans = [
                        'free' => '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Gratuito</span>',
                        'basic' => '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Básico</span>',
                        'premium' => '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Premium</span>',
                        'enterprise' => '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gold-100 text-gold-800">Enterprise</span>',
                    ];
                    return $plans[$row->plan] ?? $row->plan;
                })
                ->html(),

            Column::make('Usuários', 'users_count')
                ->cell(function (Tenant $row) {
                    $count = $row->users()->count();
                    $max = $row->max_users ?? '∞';
                    return "{$count}/{$max}";
                }),

            Column::make('Armazenamento', 'storage_used')
                ->cell(function (Tenant $row) {
                    // Calcular uso de armazenamento (simulado)
                    $usedMb = 0; // Implementar lógica real
                    $maxMb = $row->max_storage_mb ?? '∞';
                    return "{$usedMb}MB/{$maxMb}MB";
                }),

            Column::make('Expira em', 'expires_at')
                ->cell(function (Tenant $row) {
                    if (!$row->expires_at) {
                        return 'Nunca';
                    }
                    
                    $expiresAt = \Carbon\Carbon::parse($row->expires_at);
                    $now = now();
                    
                    if ($expiresAt->isPast()) {
                        return '<span class="text-red-600 font-medium">Expirado</span>';
                    } elseif ($expiresAt->diffInDays($now) <= 7) {
                        return '<span class="text-orange-600 font-medium">' . $expiresAt->format('d/m/Y') . '</span>';
                    }
                    
                    return $expiresAt->format('d/m/Y');
                })
                ->html(),

            Column::make('Criado em', 'created_at')
                ->sortable()
                ->formatter('formatDate')
                ->options('dd/MM/yyyy HH:mm'),

            Column::make('Status', 'status')
                ->sortable()
                ->formatter('renderBadge')
                ->options(TenantStatus::variantOptions()),

            Column::actions(),
        ];

        return $columns;
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'slug', 'domain', 'email'];
    }

    protected function getFilters(): array
    {
        return [
            [
                'column' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'options' => TenantStatus::options(),
            ],
            [
                'column' => 'plan',
                'label' => 'Plano',
                'type' => 'select',
                'options' => [
                    '' => 'Todos',
                    'free' => 'Gratuito',
                    'basic' => 'Básico',
                    'premium' => 'Premium',
                    'enterprise' => 'Enterprise',
                ],
            ],
            [
                'column' => 'expires_at',
                'label' => 'Expiração',
                'type' => 'select',
                'options' => [
                    '' => 'Todos',
                    'expired' => 'Expirados',
                    'expiring_soon' => 'Expirando em 7 dias',
                    'never' => 'Nunca expira',
                ],
            ]
        ];
    }

    protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array
    {
        $tenantId = $model?->id;
        $rules = [
            'name' => ['required', 'string', 'max:255'], 
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', $isUpdate ? Rule::unique('tenants')->ignore($tenantId) : Rule::unique('tenants')],
            'domain' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9.-]+$/', $isUpdate ? Rule::unique('tenants')->ignore($tenantId) : Rule::unique('tenants')],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable'],
            'plan' => ['required', 'string', Rule::in(['free', 'basic', 'premium', 'enterprise'])],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_storage_mb' => ['nullable', 'integer', 'min:100'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'custom_settings' => ['nullable', 'json'],
            'status' => ['required', Rule::in(array_column(TenantStatus::cases(), 'value'))],
        ];

        return $rules;
    }

    protected function getWithRelations(): array
    {
        return ['users'];
    }

    protected function beforeStore(array $validatedData, Request $request): array
    {
        // Processar logo
        if (isset($validatedData['logo']) && $validatedData['logo']) {
            $validatedData['logo'] = $this->moveTemporaryFile($validatedData['logo'], 'tenants/logos');
        } else {
            unset($validatedData['logo']);
        }

        // Gerar slug se não fornecido
        if (empty($validatedData['slug'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        // Configurar limites baseado no plano
        if (!isset($validatedData['max_users'])) {
            $validatedData['max_users'] = $this->getDefaultLimitsByPlan($validatedData['plan'])['max_users'];
        }

        if (!isset($validatedData['max_storage_mb'])) {
            $validatedData['max_storage_mb'] = $this->getDefaultLimitsByPlan($validatedData['plan'])['max_storage_mb'];
        }

        return $validatedData;
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate($this->getValidationRules());
        $validatedData = $this->beforeStore($validatedData, $request);

        $tenant = $this->model::create($validatedData);

        // Criar configurações padrão do tenant
        $this->createDefaultTenantSettings($tenant);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Tenant criado com sucesso com configurações padrão aplicadas.');
    }

    protected function beforeUpdate(array $validatedData, Request $request, ?Model $modelInstance = null): array
    {
        // Processar logo
        if ($request->filled('logo')) {
            if ($validatedData['logo']) {
                $newPath = $this->moveTemporaryFile($validatedData['logo'], 'tenants/logos');
                
                // Remover logo anterior
                if ($modelInstance->logo && $newPath !== $modelInstance->logo) {
                    Storage::disk(config('filesystems.default'))->delete($modelInstance->logo);
                }
                
                $validatedData['logo'] = $newPath;
            }
        } else {
            unset($validatedData['logo']);
        }

        return $validatedData;
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $tenant = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $tenant));
        $validatedData = $this->beforeUpdate($validatedData, $request, $tenant);

        $tenant->update($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Tenant atualizado com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $tenant = $this->model::findOrFail($id);

        // Verificar se há usuários associados
        $usersCount = $tenant->users()->count();
        if ($usersCount > 0) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', "Não é possível excluir este tenant pois possui {$usersCount} usuário(s) associado(s).");
        }

        DB::transaction(function () use ($tenant) {
            // Remover logo se existir
            if ($tenant->logo) {
                Storage::disk(config('filesystems.default'))->delete($tenant->logo);
            }
            
            // Remover dados relacionados
            // TODO: Implementar limpeza de dados específicos do tenant
            
            // Excluir tenant
            $tenant->delete();
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Tenant excluído com sucesso.');
    }

    /**
     * Suspender tenant
     */
    public function suspend(string $id): RedirectResponse
    {
        $tenant = $this->model::findOrFail($id);
        
        $tenant->update(['status' => 'suspended']);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', "Tenant '{$tenant->name}' foi suspenso.");
    }

    /**
     * Reativar tenant
     */
    public function activate(string $id): RedirectResponse
    {
        $tenant = $this->model::findOrFail($id);
        
        $tenant->update(['status' => 'active']);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', "Tenant '{$tenant->name}' foi reativado.");
    }

    /**
     * Impersonar tenant (acessar como landlord)
     */
    public function impersonate(string $id): RedirectResponse
    {
        $tenant = $this->model::findOrFail($id);

        // Usar GuardManager para impersonar
        $guardManager = app(\Callcocam\LaraGatekeeper\Core\Landlord\Guards\GuardManager::class);
        
        if ($guardManager->impersonateTenant($tenant->id)) {
            return redirect()->route('dashboard')
                ->with('success', "Você está agora acessando o tenant '{$tenant->name}'.");
        }

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('error', 'Não foi possível acessar este tenant.');
    }

    /**
     * Clonar tenant
     */
    public function clone(string $id): RedirectResponse
    {
        $sourceTenant = $this->model::findOrFail($id);

        DB::transaction(function () use ($sourceTenant) {
            // Criar cópia do tenant
            $newTenant = $sourceTenant->replicate();
            $newTenant->name = $sourceTenant->name . ' (Cópia)';
            $newTenant->slug = $sourceTenant->slug . '-copy-' . time();
            $newTenant->domain = null; // Remover domínio da cópia
            $newTenant->status = 'inactive'; // Iniciar como inativo
            $newTenant->save();

            // Copiar configurações personalizadas
            if ($sourceTenant->custom_settings) {
                $newTenant->custom_settings = $sourceTenant->custom_settings;
                $newTenant->save();
            }

            // TODO: Copiar outros dados relevantes (roles, permissões específicas, etc.)
        });

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', "Tenant '{$sourceTenant->name}' foi clonado com sucesso.");
    }

    /**
     * Obter limites padrão por plano
     */
    protected function getDefaultLimitsByPlan(string $plan): array
    {
        return match ($plan) {
            'free' => ['max_users' => 5, 'max_storage_mb' => 500],
            'basic' => ['max_users' => 25, 'max_storage_mb' => 2000],
            'premium' => ['max_users' => 100, 'max_storage_mb' => 10000],
            'enterprise' => ['max_users' => null, 'max_storage_mb' => null],
            default => ['max_users' => 5, 'max_storage_mb' => 500],
        };
    }

    /**
     * Criar configurações padrão do tenant
     */
    protected function createDefaultTenantSettings(Tenant $tenant): void
    {
        // TODO: Implementar criação de configurações padrão
        // - Criar roles padrão para o tenant
        // - Criar permissões específicas
        // - Configurar estrutura inicial
    }

    protected function getExtraDataForIndex(): array
    {
        return [
            'statistics' => [
                'total_tenants' => Tenant::count(),
                'active_tenants' => Tenant::where('status', 'active')->count(),
                'suspended_tenants' => Tenant::where('status', 'suspended')->count(),
                'expired_tenants' => Tenant::where('expires_at', '<', now())->count(),
                'expiring_soon' => Tenant::where('expires_at', '>', now())
                    ->where('expires_at', '<', now()->addDays(7))
                    ->count(),
                'by_plan' => Tenant::selectRaw('plan, COUNT(*) as count')
                    ->groupBy('plan')
                    ->pluck('count', 'plan')
                    ->toArray(),
                'total_users' => User::count(),
                'total_storage_used' => 0, // TODO: Implementar cálculo real
            ],
        ];
    }
} 