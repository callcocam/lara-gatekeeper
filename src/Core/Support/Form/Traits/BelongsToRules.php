<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */ 
namespace Callcocam\LaraGatekeeper\Core\Support\Form\Traits;

trait BelongsToRules
{
    protected array $rules = [];

    public function rules(array|string $rules): static
    {
        $this->rules = array_merge($this->rules, (array) $rules);
        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function addRule(string $rule): static
    {
        $this->rules[] = $rule;
        return $this;
    }
} 