<?php
/**
 *  Created by Claudio Campos.
 *  User: callcocam@gmail.com, contato@sigasmart.com.br
 *  https://www.sigasmart.com.br
 * 
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasQuery{

    /**
     * @var Builder
     */
    protected ?Builder $query = null;

    /**
     * @param Builder $query
     * @return self
     * @author Claudio Campos <callcocam@gmail.com>
     * @date 2022-08-26
     * @time 10:00
     */
    public function query(Builder $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }
}