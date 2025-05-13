<?php

namespace Callcocam\LaraGatekeeper\Core\Support;

use function Aws\is_associative;

class MultiLevelSelect extends Field
{
    protected array $apiConfig = [];
    protected ?string $apiUrl = null;
    protected ?int $levels = null;
    protected ?string $valueKey = null;
    protected ?string $labelKey = null;
    protected ?string $labelFormat = null;
    protected ?array $initialValues = null;

    public function __construct(string $key, string $label)
    {
        parent::__construct($key, $label);
        $this->type = 'multiLevelSelect';
    }

    public function apiUrl(string $url): static
    {
        $this->apiUrl = $url;
        return $this;
    }

    public function levels(int $levels): static
    {
        $this->levels = $levels;
        return $this;
    }

    public function valueKey(string $key): static
    {
        $this->valueKey = $key;
        return $this;
    }

    public function labelKey(string $key): static
    {
        $this->labelKey = $key;
        return $this;
    }

    public function labelFormat(string $format): static
    {
        $this->labelFormat = $format;
        return $this;
    }

    public function initialValues(array $values): static
    {
        if (is_associative($values)) {
            foreach ($values as $key => $value) {
                $array = [];
                foreach ($value as $id => $name) {
                    $array = [
                        'id' => $id,
                        'name' => $name
                    ];
                }
                $this->initialValues[$key] = $array;
            }
        } else {
            $this->initialValues = $values;
        }
        return $this;
    }

    public function apiConfig(array $config): static
    {
        $this->apiConfig = $config;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'apiUrl' => $this->apiUrl,
            'levels' => $this->levels,
            'valueKey' => $this->valueKey,
            'labelKey' => $this->labelKey,
            'labelFormat' => $this->labelFormat,
            'initialValues' => $this->initialValues,
            'apiConfig' => $this->apiConfig,
        ]);
    }
}
