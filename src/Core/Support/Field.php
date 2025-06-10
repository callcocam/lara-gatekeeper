<?php

namespace Callcocam\LaraGatekeeper\Core\Support;

use Closure;

class Field
{
    use Concerns\HasTypes;
    use Concerns\HasOptions;
    use Concerns\HasTextarea;
    use Concerns\HasCombobox;
    public string $key;
    public string $name;
    public string $label;
    public string $type = 'text'; // Default type
    public bool $required = false;
    public ?int $colSpan = null; // Default to full width if not specified
    public array $options = [];
    public array $inputProps = [];
    public mixed $condition = true; // Can be boolean or Closure
    public ?int $gridCols = null; // For checkboxList, radioGroup layouts
    public ?string $description = null;
    public ?string $accept = null; // For file inputs
    public ?bool $multiple = false; // For file inputs
    public ?string $relationship = null; // For related fields
    public ?string $labelAttribute = null; // For related fields
    public ?string $valueAttribute = null; // For related fields

    protected function __construct(string $key, string $label)
    {
        $this->key = $key;
        $this->name = $key;
        $this->label = $label;
    }

    public static function make(string $key, string $label): self
    {
        return new static($key, $label);
    }

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;
        return $this;
    }

    public function colSpan(int $span): self
    {
        $this->colSpan = $span;
        return $this;
    }

    // public function options(array $options): self
    // {
    //     $this->options = $options;
    //     return $this;
    // }

    // Method to set generic input properties
    public function props(array $props): self
    {
        $this->inputProps = array_merge($this->inputProps, $props);
        return $this;
    }

    // Shortcut for placeholder
    public function placeholder(string $placeholder): self
    {
        $this->inputProps['placeholder'] = $placeholder;
        return $this;
    }

     // Shortcut for accept attribute (file types)
     public function accept(string $accept): self
     {
        $this->accept = $accept; 
        return $this;
     }

    // Set display condition (can be a boolean or a Closure)
    public function when(mixed $condition): self
    {
        $this->condition = $condition;
        return $this;
    }

    public function gridCols(int $cols): self
    {
        $this->gridCols = $cols;
        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function relationship(string $relationship, string $labelAttribute = 'name', string $valueAttribute = 'id'): self
    {
        $this->relationship = $relationship;
        $this->labelAttribute = $labelAttribute;
        $this->valueAttribute = $valueAttribute;
        return $this;
    }

    // Resolve the condition
    protected function resolveCondition(): bool
    {
        if ($this->condition instanceof Closure) {
            return (bool) call_user_func($this->condition);
        }
        return (bool) $this->condition;
    }

    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function acceptedFileTypes(string $acceptedFileTypes): self
    {
        $this->acceptedFileTypes = $acceptedFileTypes;
        return $this;
    }

    public function resolveRelationship($modelInstance, $labelAttribute = 'name', $valueAttribute = 'id'): self
    {
        if ($this->relationship) {
            if (method_exists($modelInstance, $this->relationship)) {
                $this->options = $modelInstance->{$this->relationship}->pluck($labelAttribute, $valueAttribute)->toArray();
            } 
        }
        return $this;
    }

    // Convert the field definition to an array for the frontend
    public function toArray(): ?array
    {
        // Don't render the field if the condition is false
        if (!$this->resolveCondition()) {
            return null;
        }

        $data = [
            'key' => $this->key,
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
        ];

        if ($this->apiEndpoint !== null) {
            $data['apiEndpoint'] = $this->apiEndpoint;
        }

        if ($this->colSpan !== null) {
            $data['colSpan'] = $this->colSpan;
        }
        if (!empty($this->options)) {
            $data['options'] = $this->options;
        }
        if (!empty($this->inputProps)) {
            $data['inputProps'] = $this->inputProps;
        }
        if ($this->gridCols !== null) {
            $data['gridCols'] = $this->gridCols;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
         if ($this->accept !== null) {
             $data['accept'] = $this->accept;
         }
         if ($this->relationship !== null) {
            $data['relationship'] = $this->relationship;
            $data['labelAttribute'] = $this->labelAttribute;
            $data['valueAttribute'] = $this->valueAttribute;
         }

        // Condition is resolved server-side, not passed to frontend
        // $data['condition'] = $this->condition; 

        return $data;
    }
} 