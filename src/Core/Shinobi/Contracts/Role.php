<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Core\Shinobi\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Role
{
    /**
     * Roles can belong to many users.
     *
     * @return Model
     */
    public function users(): BelongsToMany;
    
    public function hasPermissionFlags(): bool;
    public function hasPermissionThroughFlag(): bool;
}