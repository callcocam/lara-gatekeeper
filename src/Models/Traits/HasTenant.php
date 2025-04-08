<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models\Traits;

use Callcocam\LaraGatekeeper\Models\Tenant; 
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTenant
{
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
