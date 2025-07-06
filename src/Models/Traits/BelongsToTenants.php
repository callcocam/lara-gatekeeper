<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models\Traits;

use Callcocam\LaraGatekeeper\Models\Tenant;
use Callcocam\LaraGatekeeper\Core\Landlord\TenantManager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait BelongsToTenants
{
    /**
     * Boot do trait
     */
    protected static function bootBelongsToTenants()
    {
        // Aplicar scope automático de tenant se não for landlord
        static::addGlobalScope('tenant_scope', function (Builder $builder) {
            $tenantManager = app(TenantManager::class);
            $currentTenant = $tenantManager->getCurrentTenant();
            
            // Não aplicar scope se for contexto landlord
            if ($tenantManager->isLandlordContext()) {
                return;
            }
            
            if ($currentTenant) {
                $builder->whereHas('tenants', function ($query) use ($currentTenant) {
                    $query->where('tenant_id', $currentTenant->id);
                });
            }
        });
    }

    /**
     * Relacionamento com tenants
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, $this->getTenantPivotTable())
            ->withPivot($this->getTenantPivotColumns())
            ->withTimestamps();
    }

    /**
     * Verificar se pertence a um tenant específico
     */
    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenants()->where('tenant_id', $tenantId)->exists();
    }

    /**
     * Verificar se pertence ao tenant atual
     */
    public function belongsToCurrentTenant(): bool
    {
        $tenantManager = app(TenantManager::class);
        $currentTenant = $tenantManager->getCurrentTenant();
        
        if (!$currentTenant) {
            return false;
        }
        
        return $this->belongsToTenant($currentTenant->id);
    }

    /**
     * Adicionar a um tenant
     */
    public function addToTenant(int $tenantId, array $pivotData = []): bool
    {
        if ($this->belongsToTenant($tenantId)) {
            return false;
        }

        $this->tenants()->attach($tenantId, $pivotData);
        $this->clearTenantCache();
        
        return true;
    }

    /**
     * Remover de um tenant
     */
    public function removeFromTenant(int $tenantId): bool
    {
        if (!$this->belongsToTenant($tenantId)) {
            return false;
        }

        $this->tenants()->detach($tenantId);
        $this->clearTenantCache();
        
        return true;
    }

    /**
     * Sincronizar com tenants
     */
    public function syncTenants(array $tenantIds, bool $detaching = true): array
    {
        $result = $this->tenants()->sync($tenantIds, $detaching);
        $this->clearTenantCache();
        
        return $result;
    }

    /**
     * Obter tenant principal (primeiro tenant)
     */
    public function getPrimaryTenant(): ?Tenant
    {
        return $this->tenants()->first();
    }

    /**
     * Verificar se é global (não pertence a nenhum tenant específico)
     */
    public function isGlobal(): bool
    {
        return $this->tenants()->count() === 0 || $this->is_global ?? false;
    }

    /**
     * Scope: Por tenant específico
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->whereHas('tenants', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });
    }

    /**
     * Scope: Globais (sem tenant específico)
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_global', true)
              ->orWhereDoesntHave('tenants');
        });
    }

    /**
     * Scope: Excluir globais
     */
    public function scopeNotGlobal(Builder $query): Builder
    {
        return $query->where('is_global', false)
                    ->whereHas('tenants');
    }

    /**
     * Scope: Acessível pelo tenant atual
     */
    public function scopeAccessibleByCurrentTenant(Builder $query): Builder
    {
        $tenantManager = app(TenantManager::class);
        $currentTenant = $tenantManager->getCurrentTenant();
        
        if (!$currentTenant) {
            return $query->global();
        }
        
        return $query->where(function ($q) use ($currentTenant) {
            $q->global()
              ->orWhereHas('tenants', function ($subQ) use ($currentTenant) {
                  $subQ->where('tenant_id', $currentTenant->id);
              });
        });
    }

    /**
     * Obter nome da tabela pivot para tenants
     */
    protected function getTenantPivotTable(): string
    {
        $modelName = strtolower(class_basename($this));
        return "tenant_{$modelName}s";
    }

    /**
     * Obter colunas adicionais da tabela pivot
     */
    protected function getTenantPivotColumns(): array
    {
        return ['created_at', 'updated_at'];
    }

    /**
     * Limpar cache relacionado a tenants
     */
    protected function clearTenantCache(): void
    {
        $cacheKeys = [
            "model_tenants_{$this->getTable()}_{$this->id}",
            "tenant_access_{$this->getTable()}_{$this->id}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Obter estatísticas de tenants
     */
    public function getTenantStatistics(): array
    {
        return Cache::remember(
            "model_tenants_{$this->getTable()}_{$this->id}",
            3600,
            function () {
                return [
                    'total_tenants' => $this->tenants()->count(),
                    'active_tenants' => $this->tenants()->where('status', 'active')->count(),
                    'is_global' => $this->isGlobal(),
                    'primary_tenant' => $this->getPrimaryTenant()?->name,
                ];
            }
        );
    }
} 