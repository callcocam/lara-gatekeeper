<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form;

use Callcocam\LaraGatekeeper\Core\Support\Concerns;
use Callcocam\LaraGatekeeper\Core\Support\Form\Traits;

abstract class Field
{
    use Concerns\EvaluatesClosures;
    use Concerns\BelongsToName;
    use Concerns\BelongsToLabel;
    use Concerns\BelongsToDescription;
    use Concerns\BelongsToComponent;
    use Concerns\BelongsToIcon;
    use Concerns\BelongsToOptions;
    use Concerns\BelongsToId;
    use Traits\BelongsToRequired;
    use Traits\BelongsToRules;
    use Traits\BelongsToDefault;
    use Traits\BelongsToMeta;
    use Traits\HasGridLayout;
    use Traits\HasProps;

    protected mixed $default = null;
    protected array $meta = [];
    protected ?string $type = 'text';

    public function __construct(string $name, ?string $label = null)
    {
        $this->name = $name;
        $this->label = $label ?? str($name)->title()->toString();
        $this->grid(1);
    }

    public static function make(string $name, ?string $label = null): static
    {
        return new static($name, $label);
    }

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;
        return $this;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function numeric(): static
    {
        $this->type = 'numeric';
        return $this;
    }

    public function date($format = 'YYYY-MM-DD'): static
    {
        $this->type = 'date';
        $this->props['format'] = $format;
        return $this;
    }
    
    public function email(): static
    {
        $this->type = 'email';
        return $this;
    }

    public function password(): static
    {
        $this->type = 'password';
        return $this;
    }

    public function rules(array|string $rules): static
    {
        $this->rules = array_merge($this->rules, (array) $rules);
        return $this;
    }

    public function toArray($model = null): array
    {
        return [
            'component' => $this->evaluate($this->component, ['model' => $model, 'field' => $this]),
            'name' => $this->evaluate($this->name, ['model' => $model, 'field' => $this]),
            'label' => $this->evaluate($this->label, ['model' => $model, 'field' => $this]),
            'grid' => $this->evaluate($this->grid, ['model' => $model, 'field' => $this]),
            'description' => $this->evaluate($this->description, ['model' => $model, 'field' => $this]),
            'default' => $this->evaluate($this->default, ['model' => $model, 'field' => $this]),
            'icon' => $this->evaluate($this->icon, ['model' => $model, 'field' => $this]),
            
            'props' => array_merge($this->props, [
                'id' => $this->evaluate($this->id ?? $this->name, ['model' => $model, 'field' => $this]),
                'name' => $this->evaluate($this->name, ['model' => $model, 'field' => $this]),
                'required' => $this->evaluate($this->required, ['model' => $model, 'field' => $this]),
                'type' => $this->evaluate($this->type, ['model' => $model, 'field' => $this]), 
            ]),
        ];
    }
}
