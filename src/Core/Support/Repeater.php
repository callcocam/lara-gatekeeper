<?php

namespace Callcocam\LaraGatekeeper\Core\Support;

class Repeater extends Field
{
    protected array $fields = [];
    protected ?int $minItems = null;
    protected ?int $maxItems = null;
    protected bool $collapsible = true;
    protected bool $collapsed = false;
    protected ?string $addButtonLabel = null;
    protected ?string $deleteButtonLabel = null;
    protected bool $reorderable = true;
    protected bool $cloneable = false;

    protected function __construct(string $key, string $label)
    {
        parent::__construct($key, $label);
        $this->type = 'repeater';
        $this->addButtonLabel = 'Adicionar Item';
        $this->deleteButtonLabel = 'Remover';
    }

    public static function make(string $key, string $label): self
    {
        return new static($key, $label);
    }

    /**
     * Define os campos filhos do repeater
     */
    public function schema(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Define o número mínimo de itens
     */
    public function minItems(int $min): self
    {
        $this->minItems = $min;
        return $this;
    }

    /**
     * Define o número máximo de itens
     */
    public function maxItems(int $max): self
    {
        $this->maxItems = $max;
        return $this;
    }

    /**
     * Define se os itens são colapsáveis
     */
    public function collapsible(bool $collapsible = true): self
    {
        $this->collapsible = $collapsible;
        return $this;
    }

    /**
     * Define se os itens iniciam colapsados
     */
    public function collapsed(bool $collapsed = true): self
    {
        $this->collapsed = $collapsed;
        return $this;
    }

    /**
     * Define o texto do botão de adicionar
     */
    public function addButtonLabel(string $label): self
    {
        $this->addButtonLabel = $label;
        return $this;
    }

    /**
     * Define o texto do botão de remover
     */
    public function deleteButtonLabel(string $label): self
    {
        $this->deleteButtonLabel = $label;
        return $this;
    }

    /**
     * Define se os itens podem ser reordenados
     */
    public function reorderable(bool $reorderable = true): self
    {
        $this->reorderable = $reorderable;
        return $this;
    }

    /**
     * Define se os itens podem ser clonados
     */
    public function cloneable(bool $cloneable = true): self
    {
        $this->cloneable = $cloneable;
        return $this;
    }

    /**
     * Adiciona um campo individual ao schema
     */
    public function addField(Field $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    /**
     * Retorna todos os campos filhos
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Verifica se tem campos definidos
     */
    public function hasFields(): bool
    {
        return !empty($this->fields);
    }

    /**
     * Processa os campos filhos para serialização
     */
    protected function processFields(): array
    {
        $processedFields = [];
        
        foreach ($this->fields as $field) {
            if ($field instanceof Field) {
                $fieldArray = $field->toArray();
                if ($fieldArray !== null) {
                    $processedFields[] = $fieldArray;
                }
            }
        }
        
        return $processedFields;
    }

    /**
     * Define valores padrão para novos itens
     */
    public function defaultItem(array $defaultValues): self
    {
        $this->inputProps['defaultItem'] = $defaultValues;
        return $this;
    }

    /**
     * Define se deve mostrar números nos itens
     */
    public function itemNumbers(bool $showNumbers = true): self
    {
        $this->inputProps['itemNumbers'] = $showNumbers;
        return $this;
    }

    /**
     * Define um template personalizado para o título do item
     */
    public function itemLabel(string $template): self
    {
        $this->inputProps['itemLabel'] = $template;
        return $this;
    }

    /**
     * Define se deve confirmar antes de deletar
     */
    public function confirmDelete(bool $confirm = true): self
    {
        $this->inputProps['confirmDelete'] = $confirm;
        return $this;
    }

    /**
     * Define configurações de grid para os campos filhos
     */
    public function fieldGrid(int $columns = 1): self
    {
        $this->inputProps['fieldGrid'] = $columns;
        return $this;
    }

    /**
     * Sobrescreve o toArray para usar 'subFields' como esperado pelo frontend
     */
    public function toArray(): ?array
    {
        // Chama o método pai para obter a estrutura base
        $data = parent::toArray();
        
        // Se a condição não foi atendida, retorna null
        if ($data === null) {
            return null;
        }

        // Frontend espera 'subFields' em vez de 'fields'
        $data['subFields'] = $this->processFields();
        
        // Garante que sempre tenha um valor inicial de array vazio
        if (!isset($data['inputProps']['defaultValue'])) {
            $data['inputProps']['defaultValue'] = [];
        }
        
        if ($this->minItems !== null) {
            $data['minItems'] = $this->minItems;
        }
        
        if ($this->maxItems !== null) {
            $data['maxItems'] = $this->maxItems;
        }
        
        $data['collapsible'] = $this->collapsible;
        $data['collapsed'] = $this->collapsed;
        $data['reorderable'] = $this->reorderable;
        $data['cloneable'] = $this->cloneable;
        
        if ($this->addButtonLabel !== null) {
            $data['addButtonLabel'] = $this->addButtonLabel;
        }
        
        if ($this->deleteButtonLabel !== null) {
            $data['deleteButtonLabel'] = $this->deleteButtonLabel;
        }

        return $data;
    }

    /**
     * Método estático para criar um repeater simples
     */
    public static function simple(string $key, string $label, array $fields): self
    {
        return static::make($key, $label)
            ->schema($fields)
            ->addButtonLabel('Adicionar ' . $label)
            ->deleteButtonLabel('Remover')
            ->collapsible(false);
    }

    /**
     * Método estático para criar um repeater com configurações padrão
     */
    public static function withDefaults(string $key, string $label): self
    {
        return static::make($key, $label)
            ->minItems(1)
            ->maxItems(10)
            ->collapsible(true)
            ->reorderable(true)
            ->confirmDelete(true)
            ->itemNumbers(true);
    }

    /**
     * Valida as configurações do repeater
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->fields)) {
            $errors[] = "Repeater '{$this->key}' deve ter pelo menos um campo definido.";
        }

        if ($this->minItems !== null && $this->maxItems !== null && $this->minItems > $this->maxItems) {
            $errors[] = "Repeater '{$this->key}': minItems não pode ser maior que maxItems.";
        }

        if ($this->minItems !== null && $this->minItems < 0) {
            $errors[] = "Repeater '{$this->key}': minItems não pode ser negativo.";
        }

        if ($this->maxItems !== null && $this->maxItems < 1) {
            $errors[] = "Repeater '{$this->key}': maxItems deve ser pelo menos 1.";
        }

        return $errors;
    }

    /**
     * Define valores padrão específicos para cada campo
     */
    public function fieldDefaults(array $defaults): self
    {
        foreach ($this->fields as $field) {
            if ($field instanceof Field && isset($defaults[$field->key])) {
                $field->props(['defaultValue' => $defaults[$field->key]]);
            }
        }
        return $this;
    }

    /**
     * Aplica configurações em lote
     */
    public function configure(array $config): self
    {
        foreach ($config as $method => $value) {
            if (method_exists($this, $method)) {
                if (is_array($value)) {
                    $this->$method(...$value);
                } else {
                    $this->$method($value);
                }
            }
        }
        return $this;
    }

    /**
     * Define valores padrão para campos específicos via inputProps
     */
    public function setFieldDefaults(array $defaults): self
    {
        foreach ($this->fields as $field) {
            if ($field instanceof Field && isset($defaults[$field->key])) {
                $field->props(['defaultValue' => $defaults[$field->key]]);
            }
        }
        return $this;
    }

    /**
     * Cria um repeater com valores iniciais
     */
    public function withInitialItems(array $items): self
    {
        $this->inputProps['initialItems'] = $items;
        return $this;
    }

    /**
     * Define se deve inicializar com pelo menos um item vazio
     */
    public function startWithEmptyItem(bool $start = true): self
    {
        if ($start) {
            $this->inputProps['startWithEmptyItem'] = true;
        }
        return $this;
    }

    /**
     * Inicializa com o número mínimo de itens se especificado
     */
    public function initializeWithMinItems(): self
    {
        if ($this->minItems && $this->minItems > 0) {
            $defaultItem = [];
            foreach ($this->fields as $field) {
                if ($field instanceof Field) {
                    $defaultItem[$field->key] = '';
                }
            }
            
            $initialItems = array_fill(0, $this->minItems, $defaultItem);
            $this->inputProps['defaultValue'] = $initialItems;
        }
        return $this;
    }
}
