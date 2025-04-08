<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Filters;

use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToOptions;
use Illuminate\Database\Eloquent\Builder;

class SelectFilter extends Filter
{
    use BelongsToOptions;

    protected string $component = 'select-filter';

    public function apply(Builder $query, mixed $value): void
    {
        if ($value) {
            $value = explode(',', $value);
            $query->whereIn($this->column, $value);
        }
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->getOptions(),
        ]);
    }
}
