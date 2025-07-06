<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models;

use App\Models\User;
use Callcocam\LaraGatekeeper\Enums\TenantStatus;
use Callcocam\LaraGatekeeper\Models\Traits\HasAddresses;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Tenant extends AbstractModel
{
    use HasUlids, SoftDeletes, HasFactory, HasAddresses;

    protected $guarded = ['id'];

    protected $casts = [
        'settings' => 'array',
        'status' => TenantStatus::class,
        'is_primary' => 'boolean'
    ];

    protected $with = ['defaultAddress'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot(['role', 'joined_at', 'status'])
            ->withTimestamps();
    }
 
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Landlords que podem gerenciar este tenant
     */
    public function landlords(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'landlord_tenant_access', 'tenant_id', 'user_id')
            ->where('is_landlord', true)
            ->withPivot(['access_level', 'granted_at', 'granted_by'])
            ->withTimestamps();
    }

    /**
     * Usuário que criou o tenant (landlord)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Logs de impersonation neste tenant
     */
    public function impersonationLogs(): HasMany
    {
        return $this->hasMany(ImpersonationLog::class);
    }

    /**
     * Roles globais que se aplicam a este tenant
     */
    public function globalRoles(): HasMany
    {
        return $this->hasMany(Role::class)->where('is_global', true);
    }

    /**
     * Permissões globais que se aplicam a este tenant
     */
    public function globalPermissions(): HasMany
    {
        return $this->hasMany(Permission::class)->where('is_global', true);
    }

    /**
     * Verificar se está ativo
     */
    public function isActive(): bool
    {
        return $this->status->value === TenantStatus::Published->value;
    }

    /**
     * Verificar se está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verificar se um landlord pode gerenciar este tenant
     */
    public function canBeManagedBy(int $landlordId): bool
    {
        return $this->landlords()->where('user_id', $landlordId)->exists();
    }

    /**
     * Obter estatísticas do tenant
     */
    public function getStatistics(): array
    {
        return [
            'users_count' => $this->users()->count(),
            'active_users_count' => $this->users()->wherePivot('status', 'active')->count(),
            'roles_count' => $this->roles()->count(),
            'permissions_count' => $this->permissions()->count(),
            'storage_used' => 0, // TODO: Implementar cálculo real
            'last_activity' => $this->users()->max('last_login_at'),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'days_until_expiry' => $this->expires_at ? now()->diffInDays($this->expires_at, false) : null,
        ];
    }

    /**
     * Scope: Tenants ativos
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Tenants expirados
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope: Tenants expirando em breve
     */
    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        return $query->where('expires_at', '>', now())
                    ->where('expires_at', '<', now()->addDays($days));
    }

    /**
     * Scope: Por plano
     */
    public function scopeByPlan(Builder $query, string $plan): Builder
    {
        return $query->where('plan', $plan);
    }
}
