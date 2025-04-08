<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Filters;

use Callcocam\LaraGatekeeper\Core\Support\Concerns;
use Illuminate\Database\Eloquent\Builder;

abstract class Filter
{
    use Concerns\BelongsToName;
    use Concerns\BelongsToLabel;
    use Concerns\BelongsToPlaceholder;

    protected string $component;
    protected string $column;

    public function __construct(string $name, ?string $label = null)
    {
        $this->name = $name;
        $this->column = $name;
        $this->label = $label ?? str($name)->title()->toString();
    }

    public static function make(string $name, ?string $label = null): static
    {
        return new static($name, $label);
    }

    public function column(string $column): static
    {
        $this->column = $column;
        return $this;
    }

    abstract public function apply(Builder $query, mixed $value): void;

    public function toArray(): array
    {
        return [
            'column' => $this->column,
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'component' => $this->component,
            'placeholder' => $this->getPlaceholder(),
        ];
    }
} 