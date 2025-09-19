<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Concerns;

trait HasRules
{
    protected array|string $rules = [];

    public function rules(array|string $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function addRule(string $field, string $rule): self
    {
        $this->rules[$field][] = $rule;

        return $this;
    }

    public function getRules(): array
    {
        return $this->evaluate($this->rules);
    }
}
