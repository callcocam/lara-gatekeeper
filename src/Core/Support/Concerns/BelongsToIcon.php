<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;

/**
 * Trait para gerenciar o ícone de uma entidade.
 */
trait BelongsToIcon
{
    /**
     * O ícone associado à entidade.
     *
     * @var Closure|string|null
     */
    protected Closure|string|null $icon = null;

    /**
     * Tamanho do ícone.
     * 
     * @var int
     */
    protected int $iconSize = 14;

    /**
     * Define o ícone.
     *
     * @param  Closure|string|null  $icon
     * @return $this
     */
    public function icon(Closure|string|null $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Retorna o ícone associado ao recurso (opcional).
     * 
     * @return string|null
     * 
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Retorna o ícone associado ao recurso (opcional).
     * 
     * @return int
     * 
     */
    public function getIconSize()
    {
        return $this->iconSize;
    }

    /**
     * Atualiza o tamanho do ícone.
     * 
     * @return static
     * 
     */

    public function iconSize(int $size): self
    {
        $this->iconSize = $size;
        return $this;
    }

}
