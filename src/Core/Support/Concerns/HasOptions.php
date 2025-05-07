<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;
use Illuminate\Support\Collection;

trait HasOptions
{
    /**
     * Set options for the field (select, radio, checkbox groups)
     * 
     * @param array|Collection | Closure $options
     * @return $this
     */
    public function options(array|Collection|Closure $options): self
    {
        if ($options instanceof Collection) {
            $options = $options->toArray();
        }
        if ($options instanceof Closure) {
            $options = $options();
        }
        
        $this->options = $options;
        return $this;
    }

    /**
     * Set options from key-value array
     * 
     * @param array $options
     * @return $this
     */
    public function optionsFromArray(array $options): self
    {
        $formattedOptions = [];
        
        foreach ($options as $value => $label) {
            $formattedOptions[] = [
                'value' => $value,
                'label' => $label
            ];
        }
        
        $this->options = $formattedOptions;
        return $this;
    }

    /**
     * Set options from a model's static method
     * 
     * @param string $class
     * @param string $method
     * @return $this
     */
    public function optionsFromEnum(string $enumClass): self
    {
        if (class_exists($enumClass) && method_exists($enumClass, 'cases')) {
            $options = [];
            
            foreach ($enumClass::cases() as $case) {
                $options[] = [
                    'value' => $case->value,
                    'label' => $case->name
                ];
            }
            
            $this->options = $options;
        }
        
        return $this;
    }

    /**
     * Set options from a relationship
     * 
     * @param string $relationship
     * @param string $labelAttribute
     * @param string $valueAttribute
     * @return $this
     */
    public function optionsFromRelationship(string $relationship, string $labelAttribute = 'name', string $valueAttribute = 'id'): self
    {
        $this->relationship = $relationship;
        $this->labelAttribute = $labelAttribute;
        $this->valueAttribute = $valueAttribute;
        return $this;
    }

    /**
     * Set options to load asynchronously from an API endpoint
     * 
     * @param string $url API endpoint to fetch options
     * @return $this
     */
    public function asyncOptions(string $url): self
    {
        $this->inputProps['asyncUrl'] = $url;
        return $this;
    }

    /**
     * Set the option label key for complex option objects
     * 
     * @param string $key
     * @return $this
     */
    public function optionLabelKey(string $key): self
    {
        $this->inputProps['optionLabelKey'] = $key;
        return $this;
    }

    /**
     * Set the option value key for complex option objects
     * 
     * @param string $key
     * @return $this
     */
    public function optionValueKey(string $key): self
    {
        $this->inputProps['optionValueKey'] = $key;
        return $this;
    }

    /**
     * Group options by a key
     * 
     * @param string $key
     * @return $this
     */
    public function groupOptionsBy(string $key): self
    {
        $this->inputProps['groupBy'] = $key;
        return $this;
    }

    /**
     * Sort options by label
     * 
     * @param string $direction 'asc' or 'desc'
     * @return $this
     */
    public function sortOptions(string $direction = 'asc'): self
    {
        $this->inputProps['sortOptions'] = $direction;
        return $this;
    }

    /**
     * Add a custom option to create new values
     * 
     * @param bool $canCreate
     * @param string|null $createUrl
     * @return $this
     */
    public function allowCreateOption(bool $canCreate = true, ?string $createUrl = null): self
    {
        $this->inputProps['allowCreate'] = $canCreate;
        
        if ($createUrl) {
            $this->inputProps['createUrl'] = $createUrl;
        }
        
        return $this;
    }
}