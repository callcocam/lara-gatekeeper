<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models;

use Callcocam\LaraGatekeeper\Models\Auth\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ImpersonationLog extends AbstractModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Landlord que fez a impersonation
     */
    public function landlord(): BelongsTo
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    /**
     * Tenant que foi impersonado
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Usuário que foi impersonado (se aplicável)
     */
    public function impersonatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_user_id');
    }

    /**
     * Scope: Impersonations ativas
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    /**
     * Scope: Por período
     */
    public function scopeInPeriod(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    /**
     * Duração da impersonation em minutos
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->ended_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->ended_at);
    }

    /**
     * Verificar se ainda está ativa
     */
    public function getIsActiveAttribute(): bool
    {
        return is_null($this->ended_at);
    }
} 