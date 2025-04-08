<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 */
namespace Callcocam\LaraGatekeeper\Core\Support\Info\Traits;

trait HasProps
{
    protected array $props = [];

    public function props(array $props): static
    {
        $this->props = $props;
        return $this;
    }

    public function prop(string $key, $value): static
    {
        data_set($this->props, $key, $value);
        return $this;
    }

    public function getProps(): array
    {
        return $this->props;
    }

    public function getProp(string $key, $default = null)
    {
        return data_get($this->props, $key, $default);
    }

    public function min(int $min): static
    {
        $this->props['min'] = $min;
        return $this;
    }

    public function max(int $max): static
    {
        $this->props['max'] = $max;
        return $this;
    }

    public function step(int $step): static
    {
        $this->props['step'] = $step;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->props['placeholder'] = $placeholder;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->props['disabled'] = $disabled;
        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->props['readonly'] = $readonly;
        return $this;
    }
 

    public function multiple(bool $multiple = true): static
    {
        $this->props['multiple'] = $multiple;
        return $this;
    }

    public function multipleLimit(int $limit): static
    {
        $this->props['multipleLimit'] = $limit;
        return $this;
    }
    
}