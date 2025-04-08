<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * Date: 2/10/2023
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Form\Field;

class MaskInput extends Field
{
    protected string $component = 'MaskInput';

    protected ?string $type = 'text';

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

    public function mask(string $mask): static
    {
        $this->mask = $mask;
        return $this;
    }

    public function tokens(array $tokens): static
    {
        $this->tokens = array_merge($this->tokens, $tokens);
        return $this;
    }

    public function cpf(): static
    {
        return $this->mask('###.###.###-##');
    }

    public function cnpj(): static
    {
        return $this->mask('##.###.###/####-##');
    }

    public function phone(): static
    {
        return $this->mask('(##) #####-####');
    }

    public function cep(): static
    {
        return $this->mask('#####-###');
    }
 

    public function returnMasked(): static
    {
        $this->maskOption('returnMasked', true);
        return $this;
    }

    public function returnUnmasked(): static
    {
        $this->maskOption('returnUnmasked', true);
        return $this;
    }

    public function money(): static
    {
        $this->mask = 'R$ num';
        $this->tokens = [
            'num' => [
                'scale' => 2,
                'thousandsSeparator' => '.',
                'radix' => ',',
                'mapToRadix' => ['.'],
            ]
        ];
        return $this;
    }

    public function cellphone(): static
    {
        return $this->mask('(##) #####-####');
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'mask' => $this->mask,
            'tokens' => $this->tokens,
            'value' => $model ? data_get($model, $this->getName()) : $this->default,
            'maskOptions' => $this->maskOptions,
        ]);
    }
}
