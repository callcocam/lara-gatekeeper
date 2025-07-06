<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models;

use Callcocam\LaraGatekeeper\Core\Shinobi\Models\Permission as ModelsPermission; 
use Callcocam\LaraGatekeeper\Enums\PermissionStatus;
use Callcocam\LaraGatekeeper\Models\Traits\HasTenant;
use Callcocam\LaraGatekeeper\Models\Traits\BelongsToTenants;
use Callcocam\LaraGatekeeper\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Permission extends ModelsPermission
{
    use HasTenant, BelongsToTenants, SoftDeletes, HasFactory;
 

    protected $casts = [
        'status' => PermissionStatus::class,
        'is_global' => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Tenant que criou esta permissão (se não for global)
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Verificar se é uma permissão global
     */
    public function isGlobal(): bool
    {
        return $this->is_global ?? false;
    }

    /**
     * Verificar se pode ser usada por um tenant específico
     */
    public function canBeUsedByTenant(int $tenantId): bool
    {
        // Permissões globais podem ser usadas por qualquer tenant
        if ($this->isGlobal()) {
            return true;
        }

        // Permissões específicas só podem ser usadas pelo tenant que as criou
        return $this->tenant_id === $tenantId;
    }

    /**
     * Clonar permissão para outro tenant
     */
    public function cloneToTenant(int $tenantId): self
    {
        $clonedPermission = $this->replicate();
        $clonedPermission->tenant_id = $tenantId;
        $clonedPermission->is_global = false;
        $clonedPermission->name = $this->name . ' (Clone)';
        $clonedPermission->save();

        return $clonedPermission;
    }

    /**
     * Gerar permissões automaticamente para um recurso
     */
    public static function generateForResource(string $resource, int $tenantId = null, bool $isGlobal = false): array
    {
        $actions = ['view', 'create', 'edit', 'delete'];
        $permissions = [];

        foreach ($actions as $action) {
            $permission = static::firstOrCreate([
                'name' => ucfirst($action) . ' ' . ucfirst($resource),
                'slug' => $action . '_' . $resource,
                'tenant_id' => $tenantId,
                'is_global' => $isGlobal,
            ]);

            $permissions[] = $permission;
        }

        return $permissions;
    }

    /**
     * Scope: Permissões globais
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope: Permissões específicas de tenant
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
     * Scope: Por categoria
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Por recurso
     */
    public function scopeByResource(Builder $query, string $resource): Builder
    {
        return $query->where('slug', 'like', '%_' . $resource);
    }

    /**
     * Obter estatísticas da permissão
     */
    public function getStatistics(): array
    {
        return [
            'roles_count' => $this->roles()->count(),
            'users_count' => $this->users()->count(),
            'tenants_count' => $this->isGlobal() ? $this->tenants()->count() : 1,
            'is_global' => $this->isGlobal(),
            'category' => $this->category,
            'resource' => $this->getResource(),
            'action' => $this->getAction(),
        ];
    }

    /**
     * Obter recurso da permissão
     */
    public function getResource(): ?string
    {
        $parts = explode('_', $this->slug);
        return count($parts) > 1 ? end($parts) : null;
    }

    /**
     * Obter ação da permissão
     */
    public function getAction(): ?string
    {
        $parts = explode('_', $this->slug);
        return count($parts) > 1 ? $parts[0] : null;
    }
}
