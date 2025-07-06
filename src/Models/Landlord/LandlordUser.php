<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models\Landlord;

use Callcocam\LaraGatekeeper\Models\Auth\User;
use Callcocam\LaraGatekeeper\Models\Tenant;
use Callcocam\LaraGatekeeper\Models\Role;
use Callcocam\LaraGatekeeper\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class LandlordUser extends User
{
    protected $table = 'users';

    protected $casts = [
        'is_landlord' => 'boolean',
        'is_super_admin' => 'boolean',
        'last_login_at' => 'datetime',
        'settings' => 'array',
        'landlord_permissions' => 'array',
    ];

    protected $appends = [
        'managed_tenants_count',
        'global_roles_list',
        'is_impersonating',
    ];

    /**
     * Boot do model
     */
    protected static function boot()
    {
        parent::boot();

        // Aplicar scope global para usuários landlord
        static::addGlobalScope('landlord', function (Builder $builder) {
            $builder->where('is_landlord', true);
        });
    }

    /**
     * Relacionamento com tenants que o landlord pode gerenciar
     */
    public function managedTenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'landlord_tenant_access')
            ->withPivot(['access_level', 'granted_at', 'granted_by'])
            ->withTimestamps();
    }

    /**
     * Todos os tenants (acesso global para landlord)
     */
    public function allTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'created_by');
    }

    /**
     * Roles globais do landlord
     */
    public function globalRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->where(function ($query) {
                $query->where('is_global', true)
                      ->orWhereNull('tenant_id');
            })
            ->withTimestamps();
    }

    /**
     * Permissões globais diretas
     */
    public function globalPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')
            ->where(function ($query) {
                $query->where('is_global', true)
                      ->orWhereNull('tenant_id');
            })
            ->withTimestamps();
    }

    /**
     * Histórico de impersonations
     */
    public function impersonationHistory(): HasMany
    {
        return $this->hasMany(\Callcocam\LaraGatekeeper\Models\ImpersonationLog::class, 'landlord_id');
    }

    /**
     * Verificar se pode gerenciar um tenant específico
     */
    public function canManageTenant(int $tenantId): bool
    {
        // Super admin pode gerenciar todos
        if ($this->is_super_admin) {
            return true;
        }

        // Verificar acesso específico
        return $this->managedTenants()->where('tenant_id', $tenantId)->exists();
    }

    /**
     * Verificar se tem permissão global
     */
    public function hasGlobalPermission(string $permission): bool
    {
        return Cache::remember(
            "landlord_permission_{$this->id}_{$permission}",
            3600,
            function () use ($permission) {
                // Verificar permissões diretas
                if ($this->globalPermissions()->where('slug', $permission)->exists()) {
                    return true;
                }

                // Verificar permissões via roles
                foreach ($this->globalRoles as $role) {
                    if ($role->permissions()->where('slug', $permission)->exists()) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    /**
     * Verificar se tem role global
     */
    public function hasGlobalRole(string $roleSlug): bool
    {
        return $this->globalRoles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Impersonar um tenant
     */
    public function impersonateTenant(int $tenantId): bool
    {
        if (!$this->canManageTenant($tenantId)) {
            return false;
        }

        $tenant = Tenant::findOrFail($tenantId);

        // Registrar impersonation
        $this->impersonationHistory()->create([
            'tenant_id' => $tenantId,
            'started_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Configurar sessão
        session([
            'impersonating_tenant_id' => $tenantId,
            'original_landlord_id' => $this->id,
            'impersonation_started_at' => now(),
        ]);

        return true;
    }

    /**
     * Parar impersonation
     */
    public function stopImpersonation(): bool
    {
        $tenantId = session('impersonating_tenant_id');
        
        if (!$tenantId) {
            return false;
        }

        // Atualizar log
        $this->impersonationHistory()
            ->where('tenant_id', $tenantId)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        // Limpar sessão
        session()->forget([
            'impersonating_tenant_id',
            'original_landlord_id',
            'impersonation_started_at'
        ]);

        return true;
    }

    /**
     * Obter estatísticas do landlord
     */
    public function getStatistics(): array
    {
        return Cache::remember(
            "landlord_stats_{$this->id}",
            1800, // 30 minutos
            function () {
                return [
                    'managed_tenants' => $this->managedTenants()->count(),
                    'total_users_managed' => $this->getTotalManagedUsers(),
                    'active_tenants' => $this->managedTenants()->where('status', 'active')->count(),
                    'global_roles' => $this->globalRoles()->count(),
                    'global_permissions' => $this->globalPermissions()->count(),
                    'impersonations_today' => $this->impersonationHistory()
                        ->whereDate('started_at', today())
                        ->count(),
                    'last_activity' => $this->last_login_at,
                ];
            }
        );
    }

    /**
     * Total de usuários gerenciados
     */
    protected function getTotalManagedUsers(): int
    {
        $tenantIds = $this->managedTenants()->pluck('id');
        
        return User::whereHas('tenants', function ($query) use ($tenantIds) {
            $query->whereIn('tenant_id', $tenantIds);
        })->count();
    }

    /**
     * Accessor: Contagem de tenants gerenciados
     */
    public function getManagedTenantsCountAttribute(): int
    {
        return $this->managedTenants()->count();
    }

    /**
     * Accessor: Lista de roles globais
     */
    public function getGlobalRolesListAttribute(): array
    {
        return $this->globalRoles()->pluck('name')->toArray();
    }

    /**
     * Accessor: Verificar se está impersonando
     */
    public function getIsImpersonatingAttribute(): bool
    {
        return session()->has('impersonating_tenant_id');
    }

    /**
     * Scope: Super admins
     */
    public function scopeSuperAdmins(Builder $query): Builder
    {
        return $query->where('is_super_admin', true);
    }

    /**
     * Scope: Landlords ativos
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at')
                    ->where('status', 'active');
    }

    /**
     * Scope: Com atividade recente
     */
    public function scopeRecentActivity(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    /**
     * Limpar cache relacionado
     */
    public function clearCache(): void
    {
        Cache::forget("landlord_stats_{$this->id}");
        
        // Limpar cache de permissões
        $permissions = Permission::pluck('slug');
        foreach ($permissions as $permission) {
            Cache::forget("landlord_permission_{$this->id}_{$permission}");
        }
    }

    /**
     * Event listeners
     */
    protected static function booted()
    {
        static::saved(function ($landlord) {
            $landlord->clearCache();
        });

        static::deleted(function ($landlord) {
            $landlord->clearCache();
        });
    }
} 