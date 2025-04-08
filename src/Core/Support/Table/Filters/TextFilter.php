<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Filters;
 

class TextFilter extends Filter
{
    

    protected string $component = 'text-filter';

    public function apply($query, $value): void
    {
        $query->where($this->column, 'like', "%{$value}%");
    }
} 