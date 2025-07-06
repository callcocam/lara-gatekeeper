<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models\Tenant;

use Callcocam\LaraGatekeeper\Models\Auth\User;
use Callcocam\LaraGatekeeper\Models\Tenant;
use Callcocam\LaraGatekeeper\Models\Role;
use Callcocam\LaraGatekeeper\Models\Permission;
use Callcocam\LaraGatekeeper\Core\Landlord\TenantManager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TenantUser extends User
{
    protected $table = 'users';

    protected $casts = [
        'is_landlord' => 'boolean',
        'last_login_at' => 'datetime',
        'settings' => 'array',
        'tenant_permissions' => 'array',
    ];

    protected $appends = [
        'current_tenant',
        'tenant_roles_list',
        'can_switch_tenants',
    ];

    /**
     * Boot do model
     */
    protected static function boot()
    {
        parent::boot();

        // Aplicar scope global para usuários não-landlord
        static::addGlobalScope('tenant_users', function (Builder $builder) {
            $builder->where('is_landlord', false);
        });

        // Aplicar scope de tenant se disponível
        static::addGlobalScope('tenant_scope', function (Builder $builder) {
            $tenantManager = app(TenantManager::class);
            $currentTenant = $tenantManager->getCurrentTenant();
            
            if ($currentTenant) {
                $builder->whereHas('tenants', function ($query) use ($currentTenant) {
                    $query->where('tenant_id', $currentTenant->id);
                });
            }
        });
    }

    /**
     * Tenant principal do usuário
     */
    public function primaryTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Todos os tenants que o usuário tem acesso
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot(['role', 'joined_at', 'status'])
            ->withTimestamps();
    }

    /**
     * Roles específicos do tenant atual
     */
    public function tenantRoles(): BelongsToMany
    {
        $tenantManager = app(TenantManager::class);
        $currentTenant = $tenantManager->getCurrentTenant();

        return $this->belongsToMany(Role::class, 'role_user')
            ->where(function ($query) use ($currentTenant) {
                if ($currentTenant) {
                    $query->where('tenant_id', $currentTenant->id)
                          ->orWhere('is_global', true);
                } else {
                    $query->where('is_global', true);
                }
            })
            ->withTimestamps();
    }

    /**
     * Permissões específicas do tenant atual
     */
    public function tenantPermissions(): BelongsToMany
    {
        $tenantManager = app(TenantManager::class);
        $currentTenant = $tenantManager->getCurrentTenant();

        return $this->belongsToMany(Permission::class, 'permission_user')
            ->where(function ($query) use ($currentTenant) {
                if ($currentTenant) {
                    $query->where('tenant_id', $currentTenant->id)
                          ->orWhere('is_global', true);
                } else {
                    $query->where('is_global', true);
                }
            })
            ->withTimestamps();
    }

    /**
     * Verificar se tem permissão no tenant atual
     */
    public function hasTenantPermission(string $permission): bool
    {
        $tenantManager = app(TenantManager::class);
        $currentTenant = $tenantManager->getCurrentTenant();
        
        if (!$currentTenant) {
            return false;
        }

        return Cache::remember(
            "tenant_permission_{$this->id}_{$currentTenant->id}_{$permission}",
            3600,
            function () use ($permission, $currentTenant) {
                // Verificar permissões diretas
                if ($this->tenantPermissions()->where('slug', $permission)->exists()) {
                    return true;
                }

                // Verificar permissões via roles
                foreach ($this->tenantRoles as $role) {
                    if ($role->permissions()->where('slug', $permission)->exists()) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    /**
     * Verificar se tem role no tenant atual
     */
    public function hasTenantRole(string $roleSlug): bool
    {
        return $this->tenantRoles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Alternar para outro tenant
     */
    public function switchToTenant(int $tenantId): bool
    {
        // Verificar se tem acesso ao tenant
        if (!$this->tenants()->where('tenant_id', $tenantId)->exists()) {
            return false;
        }

        $tenant = Tenant::findOrFail($tenantId);

        // Verificar se tenant está ativo
        if ($tenant->status !== 'active') {
            return false;
        }

        // Configurar tenant manager
        $tenantManager = app(TenantManager::class);
        $tenantManager->setCurrentTenant($tenant);

        // Limpar cache de permissões
        $this->clearTenantCache($tenantId);

        return true;
    }

    /**
     * Obter estatísticas do usuário no tenant atual
     */
    public function getTenantStatistics(): array
    {
        $tenantManager = app(TenantManager::class);
        $currentTenant = $tenantManager->getCurrentTenant();
        
        if (!$currentTenant) {
            return [];
        }

        return Cache::remember(
            "tenant_user_stats_{$this->id}_{$currentTenant->id}",
            1800, // 30 minutos
            function () use ($currentTenant) {
                return [
                    'tenant_name' => $currentTenant->name,
                    'roles_count' => $this->tenantRoles()->count(),
                    'permissions_count' => $this->tenantPermissions()->count(),
                    'joined_at' => $this->tenants()
                        ->where('tenant_id', $currentTenant->id)
                        ->first()?->pivot?->joined_at,
                    'last_activity' => $this->last_login_at,
                    'accessible_tenants' => $this->tenants()->count(),
                ];
            }
        );
    }

    /**
     * Accessor: Tenant atual
     */
    public function getCurrentTenantAttribute(): ?Tenant
    {
        $tenantManager = app(TenantManager::class);
        return $tenantManager->getCurrentTenant();
    }

    /**
     * Accessor: Lista de roles do tenant
     */
    public function getTenantRolesListAttribute(): array
    {
        return $this->tenantRoles()->pluck('name')->toArray();
    }

    /**
     * Accessor: Pode alternar tenants
     */
    public function getCanSwitchTenantsAttribute(): bool
    {
        return $this->tenants()->count() > 1;
    }

    /**
     * Scope: Usuários do tenant específico
     */
    public function scopeOfTenant(Builder $query, int $tenantId): Builder
    {
        return $query->whereHas('tenants', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });
    }

    /**
     * Scope: Usuários ativos no tenant
     */
    public function scopeActiveInTenant(Builder $query, int $tenantId): Builder
    {
        return $query->whereHas('tenants', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->where('status', 'active');
        });
    }

    /**
     * Scope: Com role específico no tenant
     */
    public function scopeWithTenantRole(Builder $query, string $roleSlug): Builder
    {
        return $query->whereHas('tenantRoles', function ($q) use ($roleSlug) {
            $q->where('slug', $roleSlug);
        });
    }

    /**
     * Limpar cache específico do tenant
     */
    public function clearTenantCache(?int $tenantId = null): void
    {
        $tenantManager = app(TenantManager::class);
        $currentTenant = $tenantManager->getCurrentTenant();
        
        $targetTenantId = $tenantId ?? $currentTenant?->id;
        
        if ($targetTenantId) {
            Cache::forget("tenant_user_stats_{$this->id}_{$targetTenantId}");
            
            // Limpar cache de permissões
            $permissions = Permission::pluck('slug');
            foreach ($permissions as $permission) {
                Cache::forget("tenant_permission_{$this->id}_{$targetTenantId}_{$permission}");
            }
        }
    }

    /**
     * Event listeners
     */
    protected static function booted()
    {
        static::saved(function ($user) {
            $user->clearTenantCache();
        });

        static::deleted(function ($user) {
            $user->clearTenantCache();
        });
    }
} 