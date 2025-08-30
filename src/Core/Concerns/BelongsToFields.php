<?php

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Callcocam\LaraGatekeeper\Core\Support\Field;

trait BelongsToFields
{
    protected ?array $fields = [];

    public function fields(array $fields): static
    {
        foreach ($fields as $order => $field) {
            $this->addField($field, $order);
        }
        return $this;
    }

    public function getField(string $name): ?Field
    {
        return collect($this->fields)->firstWhere('name', $name);
    }

    public function addField(Field $field, $order = 0): static
    {
        $this->fields[] = $field->order($order);
        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function hasFields(): bool
    {
        return !empty($this->fields);
    }

    public function getFieldsForForm(): array
    {
        return array_map(fn(Field $field) => $field->toArray(), $this->fields);
    }
}
