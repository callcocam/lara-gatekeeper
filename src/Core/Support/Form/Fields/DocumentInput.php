<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;
 
use Callcocam\LaraGatekeeper\Core\Support\Form\Mask;

class DocumentInput extends Mask
{
    protected string $component = 'CpfInput';

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->mask('000.000.000-00');
        $this->tokens([
            '0' => '[0-9]',
        ]);
        $this->maskOptions([
            'reverse' => true,
        ]);
    } 

    public function cnpj(): static
    {
        $this->mask('00.000.000/0000-00');
        $this->component('CnpjInput');
        return $this;
    }

    public function cpf(): static
    {
        $this->mask('000.000.000-00');
        $this->component('CpfInput');
        return $this;
    }
}
