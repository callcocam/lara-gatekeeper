<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 */
namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Concerns;
use Callcocam\LaraGatekeeper\Core\Support\Form\Field;

class CheckboxListInput extends Field
{
    use Concerns\BelongsToOptions;

    protected string $component = 'CheckboxListInput';

    protected ?string $type = 'checkbox-list';

    protected ?string $maxHeight = '300px';

    protected bool $searchable = true;

    public function maxHeight(string $height): static
    {
        $this->maxHeight = $height;
        return $this;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;
        return $this;
    }

    public function getMaxHeight(): ?string
    {
        return $this->maxHeight;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function toArray($model = null): array
    {
        $value = $model ? data_get($model, $this->getName(), []) : [];
        
        if (!is_array($value)) {
            $value = explode(',', $value);
        }

        return array_merge(parent::toArray($model), [
            'maxHeight' => $this->getMaxHeight(),
            'searchable' => $this->isSearchable(),
            'options' => $this->getOptions(),
            'value' => $value,
        ]);
    }
}
