<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support;

use Callcocam\LaraGatekeeper\Core;
use Closure;
use Illuminate\Support\Str;

class Filter
{
    use Core\Concerns\FactoryPattern;
    use Core\Concerns\EvaluatesClosures;
    use Core\Concerns\BelongToRequest;
    use Core\Concerns\BelongsToIcon;
    use Core\Concerns\BelongsToId;
    use Core\Concerns\BelongsToLabel;
    use Core\Concerns\BelongsToName;
    use Core\Concerns\BelongsToOptions;

    protected string $component = 'Select';


    protected string | int | float | bool | array | null $modelValue = '';
     /**
     * Formatação da coluna
     *
     * @var Closure|string|null
     */
    protected Closure|string|null $formatUsing = null;


    public function __construct(string $name, ?string $label = null)
    {
        $this->name($name);
        $this->label($label ?? Str::ucfirst(Str::singular($name)));
        $this->id($name);

        $this->modelValue($this->getRequest()->get($name));
    }

    public function modelValue(string | int | float | bool | array | null $modelValue): self
    {
        $this->modelValue = $modelValue;
        return $this;
    }

    public function getModelValue(): string | int | float | bool | array | null
    {
        return $this->modelValue;
    }

    public function component(string $component): self
    {
        $this->component = $component;
        return $this;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    
    public function formatUsing(Closure|string|null $formatUsing): self
    {
        $this->formatUsing = $formatUsing;
        return $this;
    }

    public function getFormatUsing(): Closure|string|null
    {
        return $this->formatUsing;
    }

    public function applyFormat($query, $value)
    {
        if ($this->formatUsing instanceof Closure) {
            return $this->evaluate($this->formatUsing, [
                'query' => $query,
                'value' => $value,
            ]);
        }

        if (is_string($this->formatUsing) && class_exists($this->formatUsing)) {
            return app($this->formatUsing)->cast($value);
        }

        return $value;
    }

    public function isFormatted(): bool
    {
        return !is_null($this->formatUsing);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'icon' => $this->getIcon(),
            'options' => $this->getOptions(),
            'component' => $this->getComponent(),
            'modelValue' => $this->getModelValue(),
        ];
    }
}
