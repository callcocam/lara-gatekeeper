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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tenant extends Model
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
        return $this->belongsToMany(User::class, 'tenant_users');
    }
 
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
