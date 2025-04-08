<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait BelongsToId
{
    /**
     * The id.
     */
    protected string $id;

    /**
     * Define the id.
     * 
     * @param  string  $id
     * @return $this
     */
    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the id.
     * 
     * @return string
     * 
     */
    public function getId(): string
    {
        return $this->id;
    }
}
