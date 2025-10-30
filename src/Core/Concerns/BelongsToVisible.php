<?php


/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Trait responsável pela lógica de visibilidade de campos e actions
 * 
 * Gerencia:
 * - Condições personalizadas de visibilidade
 * - Callbacks de visibilidade específicos por contexto
 * - Integração com sistema de permissões
 */
trait BelongsToVisible
{
    use BelongsToPermission;

    // Atributos para controle de visibilidade
    protected Closure|null $visibilityCallback = null;
    protected bool|Closure|null $visible = true;
    protected bool|Closure|null $visibleWhenIndex = true;
    protected bool|Closure|null $visibleWhenCreate = true;
    protected bool|Closure|null $visibleWhenShow = true;
    protected bool|Closure|null $visibleWhenEdit = true;
    protected bool|Closure|null $visibleWhenDelete = true;

    /**
     * Define callback de condição personalizada de visibilidade
     */
    public function visibleWhen(Closure|null $callback): self
    {
        $this->visibilityCallback = $callback;
        return $this;
    }

    /**
     * Verifica visibilidade usando sistema em camadas
     * 
     * Ordem de validação:
     * 1. Condição personalizada (visibilityCallback) - prioridade máxima
     * 2. Sistema de permissões (BelongsToPermission) - autenticação, policy, permissões, legacy
     */
    public function isVisible($item = null): mixed
    {
        // Camada 1: Condição personalizada tem prioridade máxima
        if ($this->visibilityCallback) {
            $user = Auth::user();
            $result = $this->evaluate($this->visibilityCallback, [
                'item' => $item,
                'user' => $user,
                'auth' => $user
            ]);

            // Se callback retornou false explicitamente, para aqui
            if ($result === false) {
                return false;
            }

            // Se retornou true ou null, continua para próximas validações
        }

        // Camada 2: Sistema de permissões (delega para BelongsToPermission)
        if(!$this->validatePermissions($item)){
            return false;
        }
        $user = Auth::user();
        if(!$this->evaluate($this->visible, [
            'item' => $item,
            'model' => $item,
            'record' => $item,
            'user' => $user,
            'auth' => $user,
        ])){
            return false;
        }
        return true;
    }

    /**
     * Define visibilidade geral
     */
    public function visible(bool|Closure|null $visible): self
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Define visibilidade específica para index
     */
    public function visibleWhenIndex(bool|Closure|null $visibleWhenIndex): self
    {
        $this->visibleWhenIndex = $visibleWhenIndex;
        return $this;
    }

    /**
     * Define visibilidade específica para index
     */
    public function showWhenIndex(): self
    {
        return $this->visibleWhenIndex(true);
    }
    
    /**
     * Define visibilidade específica para index
     */
    public function hiddenWhenIndex(): self
    {
        return $this->visibleWhenIndex(false);
    }

    /**
     * Obtém visibilidade para index
     */
    public function getVisibleWhenIndex(): ?bool
    {
        return $this->evaluate($this->visibleWhenIndex);
    }

    /**
     * Define visibilidade específica para create
     */
    public function visibleWhenCreate(bool|Closure|null $visibleWhenCreate): self
    {
        $this->visibleWhenCreate = $visibleWhenCreate;
        return $this;
    }

    /**
     * Define visibilidade específica para create
     */
    public function showWhenCreate(): self
    {
        return $this->visibleWhenCreate(true);
    }

    /**
     * Define visibilidade específica para create
     * 
     * @return $this
     */
    public function hiddenWhenCreate(): self
    {
        return $this->visibleWhenCreate(false);
    }

    /**
     * Obtém visibilidade para create
     */
    public function getVisibleWhenCreate(): ?bool
    {
        return $this->evaluate($this->visibleWhenCreate);
    }

    /**
     * Define visibilidade específica para show
     */
    public function visibleWhenShow(bool|Closure|null $visibleWhenShow): self
    {
        $this->visibleWhenShow = $visibleWhenShow;
        return $this;
    }

    /**
     * Define visibilidade específica para show
     */
    public function showWhenShow(): self
    {
        return $this->visibleWhenShow(true);
    }

    /**
     * Define visibilidade específica para show
     */
    public function hiddenWhenShow(): self
    {
        return $this->visibleWhenShow(false);
    }

    /**
     * Obtém visibilidade para show
     */
    public function getVisibleWhenShow(): ?bool
    {
        if ($this->visible === false) {
            return false;
        }

        return $this->evaluate($this->visibleWhenShow);
    }

    /**
     * Define visibilidade específica para edit
     */
    public function visibleWhenEdit(bool|Closure|null $visibleWhenEdit): self
    {
        $this->visibleWhenEdit = $visibleWhenEdit;
        return $this;
    }

    /**
     * Define visibilidade específica para edit
     */
    public function showWhenEdit(): self
    {
        return $this->visibleWhenEdit(true);
    }

    /**
     * Define visibilidade específica para edit
     */
    public function hiddenWhenEdit(): self
    {
        return $this->visibleWhenEdit(false);
    }

    /**
     * Obtém visibilidade para edit
     */
    public function getVisibleWhenEdit(): ?bool
    {
        return $this->evaluate($this->visibleWhenEdit);
    }

    /**
     * Define visibilidade específica para delete
     */
    public function visibleWhenDelete(bool|Closure|null $visibleWhenDelete): self
    {
        $this->visibleWhenDelete = $visibleWhenDelete;
        return $this;
    }

    /**
     * Define visibilidade específica para delete
     */
    public function showWhenDelete(): self
    {
        return $this->visibleWhenDelete(true);
    }

    /**
     * Define visibilidade específica para delete
     */
    public function hiddenWhenDelete(): self
    {
        return $this->visibleWhenDelete(false);
    }

    /**
     * Obtém visibilidade para delete
     */
    public function getVisibleWhenDelete(): ?bool
    {
        return $this->evaluate($this->visibleWhenDelete);
    }

    /**
     * Métodos de conveniência que combinam visibilidade + permissões
     */

    /**
     * Verifica se é visível no contexto de index
     */
    public function isVisibleOnIndex($item = null): bool
    {
        $contextVisible = $this->getVisibleWhenIndex();
        if ($contextVisible === false) {
            return false;
        }

        return $this->isVisible($item);
    }

    /**
     * Verifica se é visível no contexto de create
     */
    public function isVisibleOnCreate($item = null): bool
    {
        $contextVisible = $this->getVisibleWhenCreate();
        if ($contextVisible === false) {
            return false;
        }

        return $this->isVisible($item);
    }

    /**
     * Verifica se é visível no contexto de show
     */
    public function isVisibleOnShow($item = null): bool
    {
        $contextVisible = $this->getVisibleWhenShow();
        if ($contextVisible === false) {
            return false;
        }

        return $this->isVisible($item);
    }

    /**
     * Verifica se é visível no contexto de edit
     */
    public function isVisibleOnEdit($item = null): bool
    {
        $contextVisible = $this->getVisibleWhenEdit();
        if ($contextVisible === false) {
            return false;
        }

        return $this->isVisible($item);
    }

    /**
     * Verifica se é visível no contexto de delete
     */
    public function isVisibleOnDelete($item = null): bool
    {
        $contextVisible = $this->getVisibleWhenDelete();
        if ($contextVisible === false) {
            return false;
        }

        return $this->isVisible($item);
    }
}
