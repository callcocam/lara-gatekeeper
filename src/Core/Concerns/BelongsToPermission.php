<?php


/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns; 

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Trait responsável por toda lógica de permissões e autorizações
 * 
 * Gerencia:
 * - Verificação de autenticação
 * - Policies do Laravel (com descoberta automática)
 * - Permissões específicas via $user->can()
 * - Sistema legacy de permissões
 */
trait BelongsToPermission
{
    // Atributos para controle de permissões
    protected string|null $policyClass = null;
    protected string|null $policyMethod = null;
    protected string|array|null $requiredPermissions = null;
    protected bool $requiresAuthentication = true;
    protected bool $autoDiscoverPolicy = true;

    /**
     * Define a policy que deve ser verificada (explicitamente)
     */
    public function policy(string $policyClass, string $method = 'view'): self
    {
        $this->policyClass = $policyClass;
        $this->policyMethod = $method;
        $this->autoDiscoverPolicy = false; // Desabilita descoberta automática
        return $this;
    }

    /**
     * Define policy usando descoberta automática baseada no modelo
     * 
     * @param string $modelClass Classe do modelo (ex: Product::class)
     * @param string $method Método da policy (ex: 'view', 'update')
     */
    public function policyFor(string $modelClass, string $method = 'view'): self
    {
        $this->policyClass = $this->discoverPolicyClass($modelClass);
        $this->policyMethod = $method;
        $this->autoDiscoverPolicy = false;
        return $this;
    }

    /**
     * Habilita descoberta automática de policy baseada no item passado para isVisible()
     */
    public function autoPolicy(string $method = 'view'): self
    {
        $this->policyMethod = $method;
        $this->autoDiscoverPolicy = true;
        return $this;
    }

    /**
     * Define permissões obrigatórias
     */
    public function requiresPermissions(string|array $permissions): self
    {
        $this->requiredPermissions = is_array($permissions) ? $permissions : [$permissions];
        return $this;
    }

    /**
     * Define se requer autenticação
     */
    public function requiresAuth(bool $required = true): self
    {
        $this->requiresAuthentication = $required;
        return $this;
    }

    /**
     * Permite acesso sem autenticação
     */
    public function allowGuests(): self
    {
        $this->requiresAuthentication = false;
        return $this;
    }

    /**
     * Descobre automaticamente a classe da policy baseada no modelo
     */
    protected function discoverPolicyClass(string $modelClass): ?string
    {
        // Remove namespace e pega só o nome da classe
        $modelName = class_basename($modelClass);
        
        // Convenção padrão: ModelPolicy
        $policyClass = "App\\Policies\\{$modelName}Policy";
        
        if (class_exists($policyClass)) {
            return $policyClass;
        }

        // Tenta outras convenções comuns
        $conventions = [
            "App\\Policies\\{$modelName}",           // Sem sufixo Policy
            "App\\Http\\Policies\\{$modelName}Policy", // Subpasta Http
            "App\\Domain\\Policies\\{$modelName}Policy", // Arquitetura DDD
        ];

        foreach ($conventions as $convention) {
            if (class_exists($convention)) {
                return $convention;
            }
        }

        // Se não encontrou, loga em debug e retorna null
        if (config('app.debug')) {
            logger()->debug("Policy não encontrada para modelo: {$modelClass}", [
                'tried_conventions' => array_merge([$policyClass], $conventions)
            ]);
        }

        return null;
    }

    /**
     * Resolve a policy class baseada no item (se autodiscovery estiver ativa)
     */
    protected function resolvePolicyClass($item = null): ?string
    {
        // Se policy foi definida explicitamente, usa ela
        if ($this->policyClass && !$this->autoDiscoverPolicy) {
            return $this->policyClass;
        }

        // Se não tem item, não pode descobrir automaticamente
        if (!$item) {
            return $this->policyClass;
        }

        // Descobre policy baseada na classe do item
        $modelClass = is_string($item) ? $item : get_class($item);
        return $this->discoverPolicyClass($modelClass);
    }

    /**
     * Verifica se usuário está autenticado (se requerido)
     */
    protected function checkAuthentication(): bool
    {
        if ($this->requiresAuthentication && !Auth::check()) {
            return false;
        }
        return true;
    }

    /**
     * Verifica autorização via Policy (com descoberta automática)
     */
    protected function checkPolicyAuthorization($user, $item = null): bool
    {
        $policyClass = $this->resolvePolicyClass($item);
        $policyMethod = $this->policyMethod;

        if (!$policyClass || !$policyMethod || !$user) {
            return true; // Se não há policy definida, passa
        }

        try {
            if ($item && !is_string($item)) {
                // Verifica policy com o item específico
                return Gate::forUser($user)->check($policyMethod, $item);
            } else {
                // Verifica policy geral (para classe ou sem item)
                return Gate::forUser($user)->check($policyMethod, $policyClass);
            }
        } catch (\Exception $e) {
            // Em caso de erro na policy, loga e retorna false por segurança
            if (config('app.debug')) {
                logger()->warning("Erro na verificação de policy: {$e->getMessage()}", [
                    'policy_class' => $policyClass,
                    'policy_method' => $policyMethod,
                    'user_id' => $user->id ?? null,
                    'item' => $item ? (is_string($item) ? $item : get_class($item)) : null,
                    'auto_discovered' => $this->autoDiscoverPolicy
                ]);
            }
            return false;
        }
    }

    /**
     * Verifica se usuário tem as permissões necessárias
     */
    protected function checkUserPermissions($user): bool
    {
        if (empty($this->requiredPermissions) || !$user) {
            return true; // Se não há permissões definidas, passa
        }

        foreach ($this->requiredPermissions as $permission) {
            if (!$user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica permissões do sistema legacy (hasPermission)
     * Este método deve ser implementado pela classe que usa o trait
     */
    protected function checkLegacyPermissions(): bool
    {
        // Se a classe tem o método hasPermission, usa ele
        if (method_exists($this, 'hasPermission')) {
            return $this->hasPermission();
        }
        
        // Se não tem, assume que tem permissão (padrão seguro para novos componentes)
        return true;
    }

    /**
     * Executa todas as verificações de permissão em sequência
     * 
     * @param mixed $item Item específico para verificação (opcional)
     * @return bool
     */
    protected function validatePermissions($item = null): bool
    {
        // Etapa 1: Verificar autenticação
        if (!$this->checkAuthentication()) {
            return false;
        }

        $user = Auth::user();

        // Etapa 2: Verificar Policy (com descoberta automática se habilitada)
        if (!$this->checkPolicyAuthorization($user, $item)) {
            return false;
        }

        // Etapa 3: Verificar Permissões Específicas
        if (!$this->checkUserPermissions($user)) {
            return false;
        }

        // Etapa 4: Sistema legacy de permissões (compatibilidade)
        if (!$this->checkLegacyPermissions()) {
            return false;
        }

        return true;
    }

    /**
     * Métodos utilitários para verificações rápidas
     */

    /**
     * Verifica se o usuário atual está autenticado
     */
    public function isAuthenticated(): bool
    {
        return Auth::check();
    }

    /**
     * Verifica se o usuário atual tem uma permissão específica
     */
    public function hasSpecificPermission(string $permission): bool
    {
        $user = Auth::user();
        return $user && $user->can($permission);
    }

    /**
     * Verifica se o usuário atual tem todas as permissões
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        foreach ($permissions as $permission) {
            if (!$user->can($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verifica se o usuário atual tem pelo menos uma das permissões
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica policy de forma simples
     */
    public function can(string $ability, $model = null): bool
    {
        return Gate::check($ability, $model);
    }

    /**
     * Getters para acesso aos atributos configurados
     */
    public function getPolicyClass(): ?string
    {
        return $this->policyClass;
    }

    public function getPolicyMethod(): ?string
    {
        return $this->policyMethod;
    }

    public function getRequiredPermissions(): ?array
    {
        return $this->requiredPermissions;
    }

    public function getRequiresAuthentication(): bool
    {
        return $this->requiresAuthentication;
    }

    public function getAutoDiscoverPolicy(): bool
    {
        return $this->autoDiscoverPolicy;
    }
}
