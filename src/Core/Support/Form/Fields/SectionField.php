<?php

/**
 * Campo de Seleção em Cascata
 * 
 * Esta classe implementa um campo de formulário que permite criar campos dependentes
 * em cascata, onde a seleção de um campo determina as opções disponíveis no próximo.
 * 
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToFields; 

class SectionField extends Field
{
    use BelongsToFields;

   

    /**
     * Construtor da classe
     * 
     * @param string $key Chave identificadora do campo
     * @param string $label Rótulo do campo
     */
    public function __construct(string $key, string $label)
    {
        parent::__construct($key, $label);
        $this->type('section');
    }

    /**
     * Converte o campo para array, processando a lógica de cascata
     * 
     * @param mixed $model Modelo associado (opcional)
     * @return array Representação do campo em array
     */
    public function toArray($model = null): array
    {
        // Retorna array final com campos processados
        return array_merge(parent::toArray(), [
            'fields' => $this->getFieldsForForm(),
        ]);
    }
}
