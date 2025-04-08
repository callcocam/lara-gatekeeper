<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 * */

namespace Callcocam\LaraGatekeeper\Core\Support\Form;

use Callcocam\LaraGatekeeper\Core\Support\Concerns;
use Callcocam\LaraGatekeeper\Core\Support\Form\Traits\HasGridLayout;
use Callcocam\LaraGatekeeper\Core\Support\Form\Traits\HasRecord;
use Callcocam\LaraGatekeeper\Core\Support\Form\Traits\HasRelationship;

class Sections
{
    use Concerns\EvaluatesClosures;
    use HasGridLayout;
    use Concerns\BelongsToLabel;
    use Concerns\BelongsToDescription;
    use Concerns\BelongsToComponent;
    use HasRecord;
    use HasRelationship;

    protected array $fields = [];

    public function __construct(?string $label = null)
    {
        $this->label = $label;
        $this->grid(2);
        $this->layout(2);

        $this->component('SCSection');
    }

    public static function make(?string $label = null): static
    {
        return new static($label);
    }

    public function fields(array $fields): static
    {
        foreach ($fields as $field) {
            $this->fields[] = $field;
        }

        return $this;
    }

    public function getFields(): array
    {
        return  array_map(function ($field) {
            return $field->toArray($this->getRecord());
        }, $this->fields);
    }

    public function toArray($model = null): array
    {
        $this->record($model);
        return [
            'component' => $this->getComponent(),
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'fields' => $this->getFields(),
            'layout' => $this->getLayout(),
            'grid' => $this->getGrid(),
        ];
    }
}
