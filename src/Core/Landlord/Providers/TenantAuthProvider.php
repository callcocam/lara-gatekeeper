<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Landlord\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Callcocam\LaraGatekeeper\Http\Middleware\TenantResolver;
use Callcocam\LaraGatekeeper\Core\Landlord\TenantManager;

class TenantAuthProvider extends EloquentUserProvider
{
    protected TenantManager $tenantManager;

    public function __construct($hasher, $model, TenantManager $tenantManager)
    {
        parent::__construct($hasher, $model);
        $this->tenantManager = $tenantManager;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $model = $this->createModel();

        $query = $this->newModelQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier);

        // Aplicar scope do tenant se estiver em contexto tenant
        $this->applyTenantScope($query);

        return $query->first();
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
           (count($credentials) === 1 &&
            Str::contains($this->firstCredentialKey($credentials), 'password'))) {
            return;
        }

        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }

            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        // Aplicar scope do tenant
        $this->applyTenantScope($query);

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // Verificar se o usuário pertence ao tenant atual
        if (!$this->userBelongsToCurrentTenant($user)) {
            return false;
        }

        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Aplicar scope do tenant à query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    protected function applyTenantScope($query)
    {
        $tenantId = TenantResolver::getCurrentTenantId();
        
        if (!$tenantId) {
            return;
        }

        $model = $query->getModel();

        // Verificar se o model usa o trait BelongsToTenants
        if (method_exists($model, 'getTenantColumns')) {
            $tenantColumns = $model->getTenantColumns();
            
            foreach ($tenantColumns as $column) {
                $query->where($column, $tenantId);
            }
            return;
        }

        // Verificar se tem coluna tenant_id padrão
        if ($this->hasColumn($model, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
            return;
        }

        // Verificar se tem relação com tenant
        if (method_exists($model, 'tenant')) {
            $query->whereHas('tenant', function ($tenantQuery) use ($tenantId) {
                $tenantQuery->where('id', $tenantId);
            });
            return;
        }

        // Verificar se tem relação tenants (many-to-many)
        if (method_exists($model, 'tenants')) {
            $query->whereHas('tenants', function ($tenantQuery) use ($tenantId) {
                $tenantQuery->where('tenant_id', $tenantId);
            });
        }
    }

    /**
     * Verificar se o usuário pertence ao tenant atual
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    protected function userBelongsToCurrentTenant(Authenticatable $user): bool
    {
        $tenantId = TenantResolver::getCurrentTenantId();
        
        if (!$tenantId) {
            return true; // Se não há tenant, permitir (contexto global)
        }

        // Verificar se o usuário tem tenant_id correspondente
        if (isset($user->tenant_id)) {
            return $user->tenant_id == $tenantId;
        }

        // Verificar se tem relação com tenant
        if (method_exists($user, 'tenant')) {
            $userTenant = $user->tenant;
            return $userTenant && $userTenant->id == $tenantId;
        }

        // Verificar se tem relação tenants (many-to-many)
        if (method_exists($user, 'tenants')) {
            return $user->tenants()->where('tenant_id', $tenantId)->exists();
        }

        // Verificar se usa trait BelongsToTenants
        if (method_exists($user, 'getTenantColumns')) {
            $tenantColumns = $user->getTenantColumns();
            
            foreach ($tenantColumns as $column) {
                if (isset($user->$column) && $user->$column == $tenantId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verificar se o model tem uma coluna específica
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $column
     * @return bool
     */
    protected function hasColumn($model, string $column): bool
    {
        try {
            return $model->getConnection()
                ->getSchemaBuilder()
                ->hasColumn($model->getTable(), $column);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the first key from the credential array.
     *
     * @param  array  $credentials
     * @return string|null
     */
    protected function firstCredentialKey(array $credentials)
    {
        foreach ($credentials as $key => $value) {
            return $key;
        }

        return null;
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * Gets the name of the Eloquent user model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the name of the Eloquent user model.
     *
     * @param  string  $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Create a new model query for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newModelQuery($model = null)
    {
        return is_null($model)
                ? $this->createModel()->newQuery()
                : $model->newQuery();
    }

    /**
     * Verificar se o usuário pode acessar múltiplos tenants
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return array
     */
    public function getUserTenants(Authenticatable $user): array
    {
        $tenantIds = [];

        // Verificar se tem tenant_id único
        if (isset($user->tenant_id)) {
            return [$user->tenant_id];
        }

        // Verificar se tem relação tenants (many-to-many)
        if (method_exists($user, 'tenants')) {
            return $user->tenants()->pluck('tenant_id')->toArray();
        }

        // Verificar se usa trait BelongsToTenants
        if (method_exists($user, 'getTenantColumns')) {
            $tenantColumns = $user->getTenantColumns();
            
            foreach ($tenantColumns as $column) {
                if (isset($user->$column)) {
                    $tenantIds[] = $user->$column;
                }
            }
        }

        return array_unique($tenantIds);
    }

    /**
     * Verificar se o usuário pode alternar entre tenants
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  int  $targetTenantId
     * @return bool
     */
    public function canSwitchToTenant(Authenticatable $user, int $targetTenantId): bool
    {
        $userTenants = $this->getUserTenants($user);
        
        return in_array($targetTenantId, $userTenants);
    }

    /**
     * Alternar usuário para um tenant específico
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  int  $tenantId
     * @return bool
     */
    public function switchUserToTenant(Authenticatable $user, int $tenantId): bool
    {
        if (!$this->canSwitchToTenant($user, $tenantId)) {
            return false;
        }

        // Configurar o tenant no TenantManager
        $this->tenantManager->addTenant('tenant_id', $tenantId);

        // Atualizar sessão
        session(['current_tenant_id' => $tenantId]);

        return true;
    }

    /**
     * Obter estatísticas do usuário no tenant atual
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return array
     */
    public function getUserTenantStats(Authenticatable $user): array
    {
        $tenantId = TenantResolver::getCurrentTenantId();
        
        if (!$tenantId) {
            return [];
        }

        $stats = [
            'tenant_id' => $tenantId,
            'user_id' => $user->getAuthIdentifier(),
            'can_switch_tenants' => count($this->getUserTenants($user)) > 1,
            'accessible_tenants_count' => count($this->getUserTenants($user)),
        ];

        // Adicionar informações específicas se disponíveis
        if (method_exists($user, 'roles')) {
            $stats['roles_in_tenant'] = $user->roles()
                ->where('tenant_id', $tenantId)
                ->pluck('name')
                ->toArray();
        }

        if (method_exists($user, 'permissions')) {
            $stats['permissions_in_tenant'] = $user->permissions()
                ->where('tenant_id', $tenantId)
                ->pluck('name')
                ->toArray();
        }

        return $stats;
    }
} 