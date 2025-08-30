<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Trait para gerenciar campos dependentes (cascading dropdowns)
 * Permite criar hierarquias de campos onde cada nível depende do anterior
 */
trait CascadingFields
{
    /**
     * Configuração dos campos dependentes
     */
    protected array $cascadingFields = [];

    /**
     * Modelo base para consultas
     */
    protected ?string $baseModel = null;

    /**
     * Coluna de relacionamento pai
     */
    protected string $parentColumn = 'category_id';

    /**
     * Coluna de exibição
     */
    protected string $displayColumn = 'name';

    /**
     * Coluna de valor
     */
    protected string $valueColumn = 'id';

    /**
     * Configura um campo dependente
     */
    public function cascadingField(
        string $fieldName,
        string $parentField,
        ?string $model = null,
        ?Closure $queryCallback = null
    ): static {
        $this->cascadingFields[$fieldName] = [
            'parent_field' => $parentField,
            'model' => $model ?: $this->baseModel,
            'query_callback' => $queryCallback
        ];
        
        return $this;
    }

    /**
     * Configura múltiplos campos dependentes
     */
    public function cascadingFields(array $fields): static
    {
        foreach ($fields as $fieldName => $config) {
            if (is_string($config)) {
                // Configuração simples: apenas nome do campo pai
                $this->cascadingField($fieldName, $config);
            } elseif (is_array($config)) {
                // Configuração detalhada
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
     * Define o modelo base para consultas
     */
    public function setBaseModel(string $model): static
    {
        $this->baseModel = $model;
        return $this;
    }

    /**
     * Define as colunas padrão
     */
    public function setColumns(string $parentColumn, string $displayColumn = 'name', string $valueColumn = 'id'): static
    {
        $this->parentColumn = $parentColumn;
        $this->displayColumn = $displayColumn;
        $this->valueColumn = $valueColumn;
        
        return $this;
    }

    /**
     * Obtém opções para um campo dependente
     */
    public function getCascadingOptions(string $fieldName, Request $request = null): array
    {
        $request = $request ?: request();
        
        if (!isset($this->cascadingFields[$fieldName])) {
            return [];
        }

        $config = $this->cascadingFields[$fieldName];
        $parentValue = $request->get($config['parent_field']);

        if (!$parentValue) {
            return [];
        }

        $model = $config['model'] ?: $this->baseModel;
        if (!$model) {
            return [];
        }

        try {
            $query = $model::query()->where($this->parentColumn, $parentValue);

            // Aplica callback customizado se fornecido
            if ($config['query_callback'] instanceof Closure) {
                $query = $config['query_callback']($query, $request);
            }

            return $query->pluck($this->displayColumn, $this->valueColumn)->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtém opções para todos os campos dependentes
     */
    public function getAllCascadingOptions(Request $request = null): array
    {
        $request = $request ?: request();
        $options = [];

        foreach (array_keys($this->cascadingFields) as $fieldName) {
            $options[$fieldName] = $this->getCascadingOptions($fieldName, $request);
        }

        return $options;
    }

    /**
     * Verifica se um campo tem dependências
     */
    public function hasCascadingDependency(string $fieldName): bool
    {
        return isset($this->cascadingFields[$fieldName]);
    }

    /**
     * Obtém o campo pai de um campo dependente
     */
    public function getParentField(string $fieldName): ?string
    {
        return $this->cascadingFields[$fieldName]['parent_field'] ?? null;
    }

    /**
     * Obtém a hierarquia completa de campos
     */
    public function getFieldHierarchy(): array
    {
        $hierarchy = [];
        
        foreach ($this->cascadingFields as $fieldName => $config) {
            $hierarchy[$fieldName] = [
                'parent' => $config['parent_field'],
                'model' => $config['model'],
                'has_parent' => isset($this->cascadingFields[$config['parent_field']])
            ];
        }
        
        return $hierarchy;
    }

    /**
     * Obtém campos de nível raiz (sem pai)
     */
    public function getRootFields(): array
    {
        $rootFields = [];
        
        foreach ($this->cascadingFields as $fieldName => $config) {
            if (!isset($this->cascadingFields[$config['parent_field']])) {
                $rootFields[] = $fieldName;
            }
        }
        
        return $rootFields;
    }

    /**
     * Obtém campos filhos de um campo específico
     */
    public function getChildFields(string $parentField): array
    {
        $childFields = [];
        
        foreach ($this->cascadingFields as $fieldName => $config) {
            if ($config['parent_field'] === $parentField) {
                $childFields[] = $fieldName;
            }
        }
        
        return $childFields;
    }

    /**
     * Reseta todas as configurações de campos dependentes
     */
    public function resetCascadingFields(): static
    {
        $this->cascadingFields = [];
        $this->baseModel = null;
        
        return $this;
    }

    /**
     * Obtém estatísticas dos campos dependentes
     */
    public function getCascadingStats(): array
    {
        return [
            'total_fields' => count($this->cascadingFields),
            'root_fields' => count($this->getRootFields()),
            'base_model' => $this->baseModel,
            'parent_column' => $this->parentColumn,
            'display_column' => $this->displayColumn,
            'value_column' => $this->valueColumn,
            'hierarchy' => $this->getFieldHierarchy()
        ];
    }
} 