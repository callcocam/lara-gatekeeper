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

class LandlordAuthProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $model = $this->createModel();

        return $this->newModelQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->where(function ($query) {
                // Verificar se o usuário tem permissões de landlord
                $this->addLandlordConstraints($query);
            })
            ->first();
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

            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        // Adicionar restrições específicas do landlord
        $this->addLandlordConstraints($query);

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
        // Verificar se o usuário tem permissões de landlord
        if (!$this->userHasLandlordPermissions($user)) {
            return false;
        }

        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Adicionar restrições específicas do landlord à query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    protected function addLandlordConstraints($query)
    {
        $model = $query->getModel();

        // Verificar se o model tem método para verificar landlord
        if (method_exists($model, 'scopeLandlord')) {
            $query->landlord();
            return;
        }

        // Verificar se tem coluna is_landlord
        if ($this->hasColumn($model, 'is_landlord')) {
            $query->where('is_landlord', true);
            return;
        }

        // Verificar por roles específicas
        if (method_exists($model, 'roles')) {
            $query->whereHas('roles', function ($roleQuery) {
                $roleQuery->whereIn('name', ['landlord', 'super-admin', 'admin']);
            });
            return;
        }

        // Verificar por permissões específicas
        if (method_exists($model, 'permissions')) {
            $query->whereHas('permissions', function ($permissionQuery) {
                $permissionQuery->where('name', 'like', 'landlord.%')
                    ->orWhere('name', 'landlord.*');
            });
        }
    }

    /**
     * Verificar se o usuário tem permissões de landlord
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    protected function userHasLandlordPermissions(Authenticatable $user): bool
    {
        // Verificar flag is_landlord
        if (isset($user->is_landlord) && $user->is_landlord) {
            return true;
        }

        // Verificar roles específicas
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('landlord') || 
                $user->hasRole('super-admin') || 
                $user->hasRole('admin')) {
                return true;
            }
        }

        // Verificar permissões específicas
        if (method_exists($user, 'hasPermission')) {
            if ($user->hasPermission('landlord.access') ||
                $user->hasPermission('landlord.*')) {
                return true;
            }
        }

        // Verificar se tem relação com landlord
        if (method_exists($user, 'isLandlord')) {
            return $user->isLandlord();
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
     * Verificar se o usuário pode acessar um tenant específico
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  int  $tenantId
     * @return bool
     */
    public function canAccessTenant(Authenticatable $user, int $tenantId): bool
    {
        // Landlord pode acessar todos os tenants por padrão
        if ($this->userHasLandlordPermissions($user)) {
            return true;
        }

        // Verificar permissões específicas do tenant
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission("tenant.{$tenantId}.access") ||
                   $user->hasPermission('tenant.*.access');
        }

        return false;
    }

    /**
     * Obter todos os tenants que o usuário pode acessar
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return array
     */
    public function getAccessibleTenants(Authenticatable $user): array
    {
        if (!$this->userHasLandlordPermissions($user)) {
            return [];
        }

        // Se é super admin ou landlord, pode acessar todos
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('super-admin') || $user->hasRole('landlord')) {
                return ['*']; // Indica acesso a todos
            }
        }

        // Buscar tenants específicos baseado em permissões
        $tenantIds = [];
        if (method_exists($user, 'permissions')) {
            $permissions = $user->permissions()
                ->where('name', 'like', 'tenant.%.access')
                ->pluck('name');

            foreach ($permissions as $permission) {
                if (preg_match('/tenant\.(\d+)\.access/', $permission, $matches)) {
                    $tenantIds[] = (int) $matches[1];
                }
            }
        }

        return $tenantIds;
    }
} 