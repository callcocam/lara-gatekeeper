<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support;

use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToPermission;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Classe helper para validação de visibilidade de campos e actions
 * 
 * Usa o BelongsToPermission para toda lógica de permissões,
 * focando apenas na orquestração da validação de visibilidade.
 * 
 * Suporte completo a descoberta automática de policies!
 * 
 * Exemplo de uso:
 * 
 * // Policy explícita
 * VisibilityValidator::check($product)
 *   ->policy(ProductPolicy::class, 'view')
 *   ->isVisible();
 * 
 * // Descoberta automática por modelo
 * VisibilityValidator::check($product)
 *   ->policyFor(Product::class, 'view')
 *   ->isVisible();
 * 
 * // Descoberta automática pelo item
 * VisibilityValidator::check($product)
 *   ->autoPolicy('update')
 *   ->isVisible();
 */
class VisibilityValidator
{
    use BelongsToPermission;

    protected $item = null;
    protected Closure|null $condition = null;

    public function __construct($item = null)
    {
        $this->item = $item;
    }

    /**
     * Cria uma nova instância do validador
     */
    public static function check($item = null): self
    {
        return new self($item);
    }

    /**
     * Define condição personalizada que será verificada primeiro
     */
    public function when(Closure $condition): self
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Executa a validação de visibilidade
     */
    public function isVisible(): bool
    {
        // Camada 1: Condição personalizada tem prioridade
        if ($this->condition) {
            $user = Auth::user();
            $result = call_user_func($this->condition, $this->item, $user);
            
            // Se retornou false explicitamente, para aqui
            if ($result === false) {
                return false;
            }
        }

        // Camada 2: Sistema de permissões (delega para BelongsToPermission)
        return $this->validatePermissions($this->item);
    }

    /**
     * Métodos estáticos para validações comuns
     * Estes métodos criam instâncias temporárias para usar o BelongsToPermission
     */

    /**
     * Verifica se o usuário está autenticado
     */
    public static function isAuthenticated(): bool
    {
        return (new self())->isAuthenticated();
    }

    /**
     * Verifica se o usuário tem uma permissão específica
     */
    public static function hasPermission(string $permission): bool
    {
        return (new self())->hasSpecificPermission($permission);
    }

    /**
     * Verifica se o usuário tem todas as permissões
     */
    public static function hasAllPermissions(array $permissions): bool
    {
        return (new self())->hasAllPermissions($permissions);
    }

    /**
     * Verifica se o usuário tem pelo menos uma das permissões
     */
    public static function hasAnyPermission(array $permissions): bool
    {
        return (new self())->hasAnyPermission($permissions);
    }

    /**
     * Verifica policy de forma estática
     */
    public static function can(string $ability, $model = null): bool
    {
        return (new self())->can($ability, $model);
    }

    /**
     * Métodos de conveniência para cenários comuns
     */

    /**
     * Cria validador que requer autenticação
     */
    public static function authenticated(): self
    {
        return self::check()->requiresAuth(true);
    }

    /**
     * Cria validador que permite visitantes
     */
    public static function guests(): self
    {
        return self::check()->allowGuests();
    }

    /**
     * Cria validador com permissões específicas
     */
    public static function withPermissions(string|array $permissions): self
    {
        return self::check()->requiresPermissions($permissions);
    }

    /**
     * Cria validador com policy específica (explícita)
     */
    public static function withPolicy(string $policyClass, string $method = 'view'): self
    {
        return self::check()->policy($policyClass, $method);
    }

    /**
     * Cria validador com descoberta automática de policy baseada no modelo
     */
    public static function withPolicyFor(string $modelClass, string $method = 'view'): self
    {
        return self::check()->policyFor($modelClass, $method);
    }

    /**
     * Cria validador com descoberta automática baseada no item
     */
    public static function withAutoPolicy(string $method = 'view'): self
    {
        return self::check()->autoPolicy($method);
    }

    /**
     * Métodos de conveniência para itens específicos
     */

    /**
     * Valida se pode visualizar um item (descobre policy automaticamente)
     */
    public static function canView($item): bool
    {
        return self::check($item)->autoPolicy('view')->isVisible();
    }

    /**
     * Valida se pode editar um item (descobre policy automaticamente)
     */
    public static function canEdit($item): bool
    {
        return self::check($item)->autoPolicy('update')->isVisible();
    }

    /**
     * Valida se pode deletar um item (descobre policy automaticamente)
     */
    public static function canDelete($item): bool
    {
        return self::check($item)->autoPolicy('delete')->isVisible();
    }

    /**
     * Valida se pode criar um item (baseado na classe)
     */
    public static function canCreate(string $modelClass): bool
    {
        return self::check()->policyFor($modelClass, 'create')->isVisible();
    }

    /**
     * Métodos para cenários específicos de negócio
     */

    /**
     * Valida acesso apenas em horário comercial
     */
    public static function businessHours(): self
    {
        return self::check()->when(function() {
            $now = now();
            return $now->hour >= 8 && $now->hour <= 18 && $now->isWeekday();
        });
    }

    /**
     * Valida se usuário é o dono do item
     */
    public static function ownerOnly($item): self
    {
        return self::check($item)->when(function($item, $user) {
            return $item && $user && 
                   (property_exists($item, 'user_id') && $item->user_id === $user->id);
        });
    }

    /**
     * Valida se item está ativo/publicado
     */
    public static function activeOnly($item): self
    {
        return self::check($item)->when(function($item) {
            if (!$item) return false;
            
            // Tenta diferentes campos comuns de status
            $statusFields = ['status', 'is_active', 'published', 'is_published'];
            foreach ($statusFields as $field) {
                if (property_exists($item, $field)) {
                    return in_array($item->$field, ['active', 'published', true, 1]);
                }
            }
            
            return true; // Se não tem campo de status, assume ativo
        });
    }
} 