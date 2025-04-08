<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form;

abstract class Mask extends Field
{
    protected ?string $mask = null;

    protected array $tokens = [];

    protected ?array $maskOptions = [];
    public function maskOption(string $key, mixed $value): static
    {
        $this->maskOptions[$key] = $value;
        return $this;
    }

    public function maskOptions(array $options): static
    {
        $this->maskOptions = $options;
        return $this;
    }

    public function cpf(): static
    {
        return $this->mask('000.000.000-00');
    }

    public function cnpj(): static
    {
        return $this->mask('00.000.000/0000-00');
    }

    public function phone(): static
    {
        return $this->mask('(00) 00000-0000');
    }
    public function cep(): static
    {
        return $this->mask('00000-000');
    }

    public function tokens(array $tokens): static
    {
        $this->tokens = array_merge($this->tokens, $tokens);
        return $this;
    }
    public function mask(string $mask): static
    {
        $this->mask = $mask;
        return $this;
    }

    public function getMask(): ?string
    {
        return $this->mask;
    }
    public function toArray($model = null): array
    {
        $props = $this->getProps();

        return array_merge(parent::toArray($model), [
            'tokens' => $this->tokens,
            'maskOptions' => $this->maskOptions,
            'props' => array_merge($props, [
                'v-mask' => $this->getMask(),
            ]),
        ]);
    }
}
