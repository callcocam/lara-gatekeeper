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
use Callcocam\LaraGatekeeper\Enums\RoleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends ModelsRole
{
    use HasTenant, SoftDeletes, HasFactory;
  
    protected $casts = [
        'special' => 'boolean',
        'status' => RoleStatus::class,
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

    public function getAccessAttribute(): array
    {
        return array_keys($this->permissions->pluck('id', 'id')->toArray());
    }

}
