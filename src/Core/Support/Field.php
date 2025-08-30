<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support;

use Callcocam\LaraGatekeeper\Core;
use Closure;
use Illuminate\Database\Eloquent\Model;

class Field
{
    use Core\Concerns\EvaluatesClosures;
    use Core\Concerns\BelongsToLabel;
    use Core\Concerns\BelongsToName;
    use Core\Concerns\BelongsToOptions;
    use Core\Concerns\BelongsToType;
    use Core\Concerns\BelongsToVisible;
    use Core\Concerns\BelongsToPermission;
    use Concerns\HasTypes;
    // use Concerns\HasOptions;
    use Concerns\HasTextarea;
    use Concerns\HasCombobox;
    public string $key;
    public bool $required = false;
    public ?int $colSpan = null; // Default to full width if not specified 
    public array $inputProps = [];
    public mixed $condition = true; // Can be boolean or Closure
    public ?int $gridCols = null; // For checkboxList, radioGroup layouts
    public ?string $description = null;
    public ?string $accept = null; // For file inputs
    // public ?bool $multiple = false; // For file inputs
    public ?string $relationship = null; // For related fields
    public ?string $labelAttribute = null; // For related fields
    public ?string $valueAttribute = null; // For related fields
    public mixed $default = null; // For tags
    public ?bool $searchable = false; // For SmartSelect
    // SmartSelect properties
    public ?string $apiUrl = null; // For API-based options
    public ?string $displayTemplate = null; // For custom display templates
    public ?array $withActions = null; // For conditional actions
    public ?array $templatesApiUrl = null; // For WorkflowStepCalculator
    public ?int $stepOrder = null; // For WorkflowStepCalculator
    public ?array $previousStepData = null; // For WorkflowStepCalculator
    public ?bool $hideLabel = false; // For hiding the label
    public ?array $fieldMappings = null; // For field mappings
    public ?string $acceptedFileTypes = 'image/*';
    public ?array $acceptedFormats = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    public ?int $maxFileSize = 15;
    public ?int $order = 0;

    protected ?Model $model = null;

    protected function __construct(string $key, string $label)
    {
        $this->key = $key;
        $this->name = $key;
        $this->label = $label;
        $this->placeholder($label);
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

    public function tags(): self
    {
        $this->type('tags');
        return $this;
    }
    public function default(mixed $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }
    public function hideLabel(): self
    {
        $this->hideLabel = true;
        return $this;
    }

    public function fieldMappings(array $mappings): self
    {
        $this->fieldMappings = $mappings;
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

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;
        $this->inputProps['searchable'] = $searchable;
        return $this;
    }

    // SmartSelect methods
    public function apiUrl(string $url): self
    {
        $this->apiUrl = $url;
        return $this;
    }

    public function displayTemplate(string $template): self
    {
        $this->displayTemplate = $template;
        return $this;
    }

    public function withActions(array $actions): self
    {
        $this->withActions = $actions;
        return $this;
    }

    // WorkflowStepCalculator methods
    public function templatesApiUrl(array $urls): self
    {
        $this->templatesApiUrl = $urls;
        return $this;
    }

    public function stepOrder(int $order): self
    {
        $this->inputProps['stepOrder'] = $order;
        return $this;
    }

    public function previousStepData(array $data): self
    {
        $this->inputProps['previousStepData'] = $data;
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

    public function maxFileSize(int $maxFileSize): self
    {
        $this->maxFileSize = $maxFileSize;
        return $this;
    }

    public function acceptedFormats(array $acceptedFormats): self
    {
        $this->acceptedFormats = $acceptedFormats;
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

    public function order(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function model(Model $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    // Convert the field definition to an array for the frontend
    public function toArray($model = null): ?array
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
            'searchable' => $this->searchable,
            'hideLabel' => $this->hideLabel,
            'fieldMappings' => $this->fieldMappings,
            'order' => $this->order,
        ];

        // WorkflowStepCalculator properties
        if ($this->templatesApiUrl !== null) {
            $data['templatesApiUrl'] = $this->templatesApiUrl;
        }
        if ($this->stepOrder !== null) {
            $data['stepOrder'] = $this->stepOrder;
        }

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

        // SmartSelect properties
        if ($this->apiUrl !== null) {
            $data['apiUrl'] = $this->apiUrl;
        }
        if ($this->displayTemplate !== null) {
            $data['displayTemplate'] = $this->displayTemplate;
        }
        if ($this->withActions !== null) {
            $data['withActions'] = $this->withActions;
        }
        if ($this->acceptedFileTypes !== null) {
            $data['acceptedFileTypes'] = $this->acceptedFileTypes;
        }
        if ($this->maxFileSize !== null) {
            $data['maxFileSize'] = $this->maxFileSize;
        }
        if ($this->acceptedFormats !== null) {
            $data['acceptedFormats'] = $this->acceptedFormats;
        }

        // Condition is resolved server-side, not passed to frontend
        // $data['condition'] = $this->condition; 

        return $data;
    }
}
