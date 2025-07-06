<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models;

use App\Models\User;
use Callcocam\LaraGatekeeper\Core\Shinobi\Models\Role as ModelsRole; 
use Callcocam\LaraGatekeeper\Models\Traits\HasTenant;
use Callcocam\LaraGatekeeper\Models\Traits\BelongsToTenants;
use Callcocam\LaraGatekeeper\Enums\RoleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Role extends ModelsRole
{
    use HasTenant, BelongsToTenants, SoftDeletes, HasFactory;
  
    protected $casts = [
        'special' => 'boolean',
        'status' => RoleStatus::class,
        'is_global' => 'boolean',
    ];

    protected $append = ['access'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Tenant que criou este role (se não for global)
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Verificar se é um role global
     */
    public function isGlobal(): bool
    {
        return $this->is_global ?? false;
    }

    /**
     * Verificar se pode ser usado por um tenant específico
     */
    public function canBeUsedByTenant(int $tenantId): bool
    {
        // Roles globais podem ser usados por qualquer tenant
        if ($this->isGlobal()) {
            return true;
        }

        // Roles específicos só podem ser usados pelo tenant que os criou
        return $this->tenant_id === $tenantId;
    }

    /**
     * Clonar role para outro tenant
     */
    public function cloneToTenant(int $tenantId): self
    {
        $clonedRole = $this->replicate();
        $clonedRole->tenant_id = $tenantId;
        $clonedRole->is_global = false;
        $clonedRole->name = $this->name . ' (Clone)';
        $clonedRole->save();

        // Clonar permissões
        $clonedRole->permissions()->sync($this->permissions->pluck('id'));

        return $clonedRole;
    }

    /**
     * Sincronizar com tenants específicos
     */
    public function syncWithTenants(array $tenantIds): void
    {
        if (!$this->isGlobal()) {
            return; // Apenas roles globais podem ser sincronizados
        }

        foreach ($tenantIds as $tenantId) {
            $this->addToTenant($tenantId);
        }
    }

    /**
     * Scope: Roles globais
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope: Roles específicos de tenant
     */
    public function scopeTenantSpecific(Builder $query): Builder
    {
        return $query->where('is_global', false);
    }

    /**
     * Scope: Acessível por tenant
     */
    public function scopeAccessibleByTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->where('is_global', true)
              ->orWhere('tenant_id', $tenantId);
        });
    }

    /**
     * Scope: Por status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Ativos
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Obter estatísticas do role
     */
    public function getStatistics(): array
    {
        return [
            'users_count' => $this->users()->count(),
            'permissions_count' => $this->permissions()->count(),
            'tenants_count' => $this->isGlobal() ? $this->tenants()->count() : 1,
            'is_global' => $this->isGlobal(),
            'is_special' => $this->special,
            'created_at' => $this->created_at,
            'last_used' => $this->users()->max('last_login_at'),
        ];
    }

    public function getAccessAttribute(): array
    {
        return array_keys($this->permissions->pluck('id', 'id')->toArray());
    }

}
