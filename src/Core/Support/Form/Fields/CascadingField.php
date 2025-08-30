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
use Closure;

class CascadingField extends Field
{
    use BelongsToFields;

    /**
     * Configuração dos campos dependentes
     * Estrutura: ['campo_filho' => 'campo_pai']
     * 
     * @var array
     */
    protected array $cascadingFields = [];

    /**
     * Callback para buscar dados dinâmicos
     * 
     * @var Closure
     */
    protected Closure $queryCallback;

    /**
     * Construtor da classe
     * 
     * @param string $key Chave identificadora do campo
     * @param string $label Rótulo do campo
     */
    public function __construct(string $key, string $label)
    {
        parent::__construct($key, $label);
        $this->type('multiLevelSelect');

        // Define callback padrão que retorna todos os dados da request
        $this->queryCallback(function ($request) {
            return $request->all();
        });
    }

    // ==========================================
    // MÉTODOS DE CONFIGURAÇÃO
    // ==========================================

    /**
     * Define o callback para buscar dados
     * 
     * @param Closure $queryCallback Função que recebe a request e retorna dados
     * @return static
     */
    public function queryCallback(Closure $queryCallback): static
    {
        $this->queryCallback = $queryCallback;
        return $this;
    }

    /**
     * Configura múltiplos campos dependentes de uma só vez
     * 
     * Aceita dois formatos:
     * - Simples: ['campo_filho' => 'campo_pai']
     * - Detalhado: ['campo_filho' => ['parent_field' => 'campo_pai', 'model' => ..., 'query_callback' => ...]]
     * 
     * @param array $fields Array de configurações dos campos
     * @return static
     */
    public function cascadingFields(array $fields): static
    {
        foreach ($fields as $fieldName => $config) {
            if (is_string($config)) {
                // Configuração simples: apenas nome do campo pai
                $this->cascadingField($fieldName, $config);
            } elseif (is_array($config)) {
                // Configuração detalhada (preparado para futuras expansões)
                $this->cascadingField(
                    $fieldName,
                    $config['parent_field'],
                    $config['model'] ?? null,
                    $config['query_callback'] ?? null
                );
            }
        }

        return $this;
    }

    /**
     * Configura um único campo dependente
     * 
     * @param string $fieldName Nome do campo filho
     * @param string $parentField Nome do campo pai
     * @return static
     */
    public function cascadingField(string $fieldName, string $parentField): static
    {
        $this->cascadingFields[$fieldName] = $parentField;
        return $this;
    }

    // ==========================================
    // MÉTODOS DE ACESSO (GETTERS)
    // ==========================================

    /**
     * Retorna todos os campos em cascata configurados
     * 
     * @return array
     */
    public function getCascadingFields(): array
    {
        return $this->cascadingFields;
    }

    /**
     * Retorna o campo pai de um campo específico
     * 
     * @param string $fieldName Nome do campo
     * @return string|null Nome do campo pai ou null se não encontrado
     */
    public function getCascadingField(string $fieldName): ?string
    {
        return $this->cascadingFields[$fieldName] ?? null;
    }

    // ==========================================
    // MÉTODOS DE PROCESSAMENTO DE DADOS
    // ==========================================

    /**
     * Obtém o valor do campo, priorizando parâmetros da query string
     * 
     * @param mixed $initialValue Valor inicial padrão
     * @return mixed Valor do campo
     */
    public function getValue($initialValue = null): mixed
    {
        // Busca parâmetros específicos do campo na query string
        $cascadingFieldParams = request()->query($this->getName(), []);

        // Se existem parâmetros, usa eles; caso contrário, usa valor inicial
        return count($cascadingFieldParams) > 0 ? $cascadingFieldParams : $initialValue;
    }

    /**
     * Converte o campo para array, processando a lógica de cascata
     * 
     * @param mixed $model Modelo associado (opcional)
     * @return array Representação do campo em array
     */
    public function toArray($model = null): array
    {
        // Auto-configuração: se não há campos em cascata configurados,
        // cria automaticamente baseado na ordem dos campos
        if (!count($this->cascadingFields)) {
            $this->autoConfigureCascadingFields();
        }

        // Obtém apenas os nomes dos campos em cascata
        $cascadingFieldNames = array_keys($this->cascadingFields);

        // Busca parâmetros específicos dos campos em cascata na query string
        $cascadingFieldParams = collect(request()->query($this->getName(), []))
            ->only($cascadingFieldNames);

        // Processa cada relação pai-filho
        $this->processCascadingRelations($cascadingFieldParams);

        // Retorna array final com campos processados
        return array_merge(parent::toArray(), [
            'fields' => $this->getFieldsForForm(),
            'cascadingFields' => $cascadingFieldNames,
        ]);
    }

    // ==========================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ==========================================

    /**
     * Configura automaticamente campos em cascata baseado na ordem
     * 
     * Se nenhum campo em cascata foi definido manualmente, 
     * cria relações sequenciais entre os campos.
     */
    private function autoConfigureCascadingFields(): void
    {
        collect($this->fields)->each(function (Field $field, $index) {
            // Busca o próximo campo na sequência
            $nextField = collect($this->fields)->get($index + 1);

            if ($nextField) {
                // Configura o campo atual como pai do próximo
                $this->cascadingField($field->getName(), $nextField->getName());
            }
        });
    }

    /**
     * Processa as relações de cascata, carregando opções dinamicamente
     * 
     * @param \Illuminate\Support\Collection $cascadingFieldParams Parâmetros dos campos
     */
    private function processCascadingRelations($cascadingFieldParams): void
    {
        foreach ($this->cascadingFields as $parentField => $childField) {
            // Busca o campo filho na coleção de campos
            $field = collect($this->fields)->firstWhere('name', $childField);

            if (!$field) {
                continue; // Campo não encontrado, pula para o próximo
            }

            $options = $this->getOptionsForField($parentField, $cascadingFieldParams);
            $field->options($options);

            // Se não há modelo associado, para o processamento
            if (!$this->getModel()) {
                break;
            }
        }
    }

    /**
     * Obtém opções para um campo específico baseado no valor do campo pai
     * 
     * @param string $parentField Nome do campo pai
     * @param \Illuminate\Support\Collection $cascadingFieldParams Parâmetros dos campos
     * @return array Opções para o campo
     */
    private function getOptionsForField(string $parentField, $cascadingFieldParams): array
    {
        // Se não há parâmetros na query, usa dados do modelo
        if ($cascadingFieldParams->isEmpty()) {
            $requestData = data_get($this->getModel(), sprintf('%s.%s', $this->getName(), $parentField));
            return $this->evaluate($this->queryCallback, ['request' => $requestData]) ?? [];
        }

        // Se há valor selecionado para o campo pai, busca opções baseadas nele
        if ($parentValue = $cascadingFieldParams->get($parentField)) {
            return $this->evaluate($this->queryCallback, ['request' => $parentValue]) ?? [];
        }

        // Caso contrário, retorna array vazio
        return [];
    }
}
