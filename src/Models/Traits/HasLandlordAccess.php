<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models\Traits;

use Callcocam\LaraGatekeeper\Models\Tenant;
use Callcocam\LaraGatekeeper\Models\ImpersonationLog;
use Callcocam\LaraGatekeeper\Core\Landlord\TenantManager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait HasLandlordAccess
{
    /**
     * Boot do trait
     */
    protected static function bootHasLandlordAccess()
    {
        // Aplicar scope para landlords
        static::addGlobalScope('landlord_scope', function (Builder $builder) {
            $builder->where('is_landlord', true);
        });
    }

    /**
     * Tenants que este landlord pode gerenciar
     */
    public function managedTenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'landlord_tenant_access')
            ->withPivot(['access_level', 'granted_at', 'granted_by'])
            ->withTimestamps();
    }

    /**
     * Todos os tenants criados por este landlord
     */
    public function createdTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'created_by');
    }

    /**
     * Histórico de impersonations
     */
    public function impersonationHistory(): HasMany
    {
        return $this->hasMany(ImpersonationLog::class, 'landlord_id');
    }

    /**
     * Verificar se pode gerenciar um tenant
     */
    public function canManageTenant(int $tenantId): bool
    {
        // Super admin pode gerenciar todos
        if ($this->is_super_admin) {
            return true;
        }

        // Verificar se tem acesso específico
        return $this->managedTenants()->where('tenant_id', $tenantId)->exists();
    }

    /**
     * Verificar se é super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin ?? false;
    }

    /**
     * Conceder acesso a um tenant
     */
    public function grantTenantAccess(int $tenantId, string $accessLevel = 'full', ?int $grantedBy = null): bool
    {
        if ($this->canManageTenant($tenantId)) {
            return false; // Já tem acesso
        }

        $this->managedTenants()->attach($tenantId, [
            'access_level' => $accessLevel,
            'granted_at' => now(),
            'granted_by' => $grantedBy ?? auth()->id(),
        ]);

        $this->clearLandlordCache();
        
        return true;
    }

    /**
     * Revogar acesso a um tenant
     */
    public function revokeTenantAccess(int $tenantId): bool
    {
        if (!$this->canManageTenant($tenantId)) {
            return false;
        }

        $this->managedTenants()->detach($tenantId);
        $this->clearLandlordCache();
        
        return true;
    }

    /**
     * Impersonar um tenant
     */
    public function impersonateTenant(int $tenantId, ?int $userId = null): bool
    {
        if (!$this->canManageTenant($tenantId)) {
            return false;
        }

        $tenant = Tenant::findOrFail($tenantId);

        // Registrar impersonation
        $this->impersonationHistory()->create([
            'tenant_id' => $tenantId,
            'impersonated_user_id' => $userId,
            'started_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'original_url' => request()->url(),
                'session_id' => session()->getId(),
            ],
        ]);

        // Configurar contexto
        $tenantManager = app(TenantManager::class);
        $tenantManager->setCurrentTenant($tenant);

        // Configurar sessão
        session([
            'impersonating_tenant_id' => $tenantId,
            'impersonating_user_id' => $userId,
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
            ->latest()
            ->first()
            ?->update(['ended_at' => now()]);

        // Limpar contexto
        $tenantManager = app(TenantManager::class);
        $tenantManager->clearCurrentTenant();

        // Limpar sessão
        session()->forget([
            'impersonating_tenant_id',
            'impersonating_user_id',
            'original_landlord_id',
            'impersonation_started_at'
        ]);

        return true;
    }

    /**
     * Verificar se está impersonando
     */
    public function isImpersonating(): bool
    {
        return session()->has('impersonating_tenant_id');
    }

    /**
     * Obter tenant sendo impersonado
     */
    public function getImpersonatedTenant(): ?Tenant
    {
        $tenantId = session('impersonating_tenant_id');
        
        if (!$tenantId) {
            return null;
        }

        return Tenant::find($tenantId);
    }

    /**
     * Obter estatísticas do landlord
     */
    public function getLandlordStatistics(): array
    {
        return Cache::remember(
            "landlord_stats_{$this->id}",
            1800, // 30 minutos
            function () {
                return [
                    'managed_tenants' => $this->managedTenants()->count(),
                    'created_tenants' => $this->createdTenants()->count(),
                    'active_tenants' => $this->managedTenants()->where('status', 'active')->count(),
                    'total_users_managed' => $this->getTotalManagedUsers(),
                    'impersonations_today' => $this->impersonationHistory()
                        ->whereDate('started_at', today())
                        ->count(),
                    'impersonations_this_month' => $this->impersonationHistory()
                        ->whereMonth('started_at', now()->month)
                        ->count(),
                    'last_impersonation' => $this->impersonationHistory()
                        ->latest('started_at')
                        ->first()?->started_at,
                    'is_super_admin' => $this->isSuperAdmin(),
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
        
        if ($tenantIds->isEmpty()) {
            return 0;
        }

        return \Callcocam\LaraGatekeeper\Models\Auth\User::whereHas('tenants', function ($query) use ($tenantIds) {
            $query->whereIn('tenant_id', $tenantIds);
        })->count();
    }

    /**
     * Scope: Super admins
     */
    public function scopeSuperAdmins(Builder $query): Builder
    {
        return $query->where('is_super_admin', true);
    }

    /**
     * Scope: Landlords regulares (não super admin)
     */
    public function scopeRegularLandlords(Builder $query): Builder
    {
        return $query->where('is_super_admin', false);
    }

    /**
     * Scope: Com acesso a tenant específico
     */
    public function scopeWithTenantAccess(Builder $query, int $tenantId): Builder
    {
        return $query->whereHas('managedTenants', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });
    }

    /**
     * Scope: Ativos recentemente
     */
    public function scopeRecentlyActive(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    /**
     * Limpar cache do landlord
     */
    public function clearLandlordCache(): void
    {
        $cacheKeys = [
            "landlord_stats_{$this->id}",
            "landlord_tenants_{$this->id}",
            "landlord_permissions_{$this->id}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Obter nível de acesso a um tenant
     */
    public function getTenantAccessLevel(int $tenantId): ?string
    {
        if ($this->isSuperAdmin()) {
            return 'super_admin';
        }

        $pivot = $this->managedTenants()
            ->where('tenant_id', $tenantId)
            ->first()?->pivot;

        return $pivot?->access_level;
    }

    /**
     * Verificar se tem nível de acesso específico
     */
    public function hasAccessLevel(int $tenantId, string $level): bool
    {
        $currentLevel = $this->getTenantAccessLevel($tenantId);
        
        if (!$currentLevel) {
            return false;
        }

        // Hierarquia de níveis
        $hierarchy = [
            'read' => 1,
            'write' => 2,
            'admin' => 3,
            'full' => 4,
            'super_admin' => 5,
        ];

        return ($hierarchy[$currentLevel] ?? 0) >= ($hierarchy[$level] ?? 0);
    }
} 