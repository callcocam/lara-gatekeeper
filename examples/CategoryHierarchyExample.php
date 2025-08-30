<?php

/**
 * Exemplo específico para categorias hierárquicas
 * Baseado no ProductController do Plannerate
 */

namespace Examples;

use Callcocam\LaraGatekeeper\Core\Concerns\RelationshipManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Exemplo de uso para categorias hierárquicas
 * Segmento Varejista → Departamento → Subdepartamento → Categoria → Subcategoria → Segmento → Subsegmento
 */
class CategoryHierarchyFilter
{
    use RelationshipManager;

    public function __construct()
    {
        // Configura o modelo base e colunas
        $this->setBaseModel(\App\Models\Category::class)
             ->setColumns('category_id', 'name', 'id');
    }

    /**
     * Configura a hierarquia de categorias
     */
    public function configureCategoryHierarchy(): static
    {
        $this->cascadingFields([
            'departamento' => 'segmento_varejista',
            'subdepartamento' => 'departamento',
            'categoria' => 'subdepartamento',
            'subcategoria' => 'categoria',
            'segmento' => 'subcategoria',
            'subsegmento' => 'segmento'
        ]);

        return $this;
    }

    /**
     * Configuração com callbacks customizados
     */
    public function configureWithCallbacks(): static
    {
        $this->cascadingField('departamento', 'segmento_varejista', null, function(Builder $query, Request $request) {
            return $query->where('active', true)->orderBy('name');
        });

        $this->cascadingField('subdepartamento', 'departamento', null, function(Builder $query, Request $request) {
            return $query->where('active', true)
                        ->where('featured', true)
                        ->orderBy('name');
        });

        $this->cascadingField('categoria', 'subdepartamento');
        $this->cascadingField('subcategoria', 'categoria');
        $this->cascadingField('segmento', 'subcategoria');
        $this->cascadingField('subsegmento', 'segmento');

        return $this;
    }

    /**
     * Obtém opções para um campo específico
     */
    public function getCategoryOptions(string $fieldName): array
    {
        return $this->getCascadingOptions($fieldName);
    }

    /**
     * Obtém todas as opções da hierarquia
     */
    public function getAllCategoryOptions(): array
    {
        return $this->getAllCascadingOptions();
    }

    /**
     * Obtém campos de nível raiz (segmento_varejista)
     */
    public function getRootCategories(): array
    {
        return \App\Models\Category::query()
            ->whereNull('category_id')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Obtém estatísticas da hierarquia
     */
    public function getHierarchyStats(): array
    {
        return $this->getCascadingStats();
    }
}

/**
 * Exemplo de uso no ProductController
 */
class ProductControllerExample
{
    protected CategoryHierarchyFilter $categoryFilter;

    public function __construct()
    {
        $this->categoryFilter = new CategoryHierarchyFilter();
        $this->categoryFilter->configureCategoryHierarchy();
    }

    /**
     * Método equivalente ao getCategoryData do ProductController
     */
    protected function getCategoryData($name = null, $model = null): array
    {
        if (!$name) {
            return [];
        }

        // Se for um campo dependente, usa o sistema de cascading
        if ($this->categoryFilter->hasCascadingDependency($name)) {
            return $this->categoryFilter->getCategoryOptions($name);
        }

        // Fallback para campos não configurados
        $value = request($name);
        if ($value) {
            return \App\Models\Category::query()
                ->where('category_id', $value)
                ->pluck('name', 'id')
                ->toArray();
        }

        return [];
    }

    /**
     * Obtém dados para o formulário
     */
    public function getFormData(): array
    {
        return [
            'hierarchy' => [
                'segmento_varejista' => $this->categoryFilter->getRootCategories(),
                'departamento' => $this->categoryFilter->getCategoryOptions('departamento'),
                'subdepartamento' => $this->categoryFilter->getCategoryOptions('subdepartamento'),
                'categoria' => $this->categoryFilter->getCategoryOptions('categoria'),
                'subcategoria' => $this->categoryFilter->getCategoryOptions('subcategoria'),
                'segmento' => $this->categoryFilter->getCategoryOptions('segmento'),
                'subsegmento' => $this->categoryFilter->getCategoryOptions('subsegmento'),
            ],
            'stats' => $this->categoryFilter->getHierarchyStats(),
            'hierarchy_info' => $this->categoryFilter->getFieldHierarchy()
        ];
    }

    /**
     * API endpoint para obter opções de um campo específico
     */
    public function getFieldOptions(Request $request): array
    {
        $fieldName = $request->get('field');
        
        if (!$fieldName) {
            return ['error' => 'Campo não especificado'];
        }

        if ($fieldName === 'segmento_varejista') {
            return [
                'options' => $this->categoryFilter->getRootCategories(),
                'field' => $fieldName,
                'is_root' => true
            ];
        }

        if ($this->categoryFilter->hasCascadingDependency($fieldName)) {
            return [
                'options' => $this->categoryFilter->getCategoryOptions($fieldName),
                'field' => $fieldName,
                'parent' => $this->categoryFilter->getParentField($fieldName),
                'children' => $this->categoryFilter->getChildFields($fieldName)
            ];
        }

        return ['error' => 'Campo não configurado'];
    }
}

/**
 * Exemplo de uso com filtros avançados
 */
class AdvancedCategoryFilter extends CategoryHierarchyFilter
{
    public function configureAdvanced(): static
    {
        // Configuração básica
        $this->configureCategoryHierarchy();

        // Adiciona filtros baseados em relacionamentos
        $this->whereRelationship('segmento_varejista', 'active', true);
        $this->whereRelationship('departamento', 'featured', true);
        
        // Filtro customizado para categorias premium
        $this->whereRelationshipCallback('categoria', function(Builder $query) {
            return $query->where('premium', true)
                        ->where('active', true);
        });

        return $this;
    }

    /**
     * Obtém categorias com filtros aplicados
     */
    public function getFilteredCategories(): array
    {
        $query = \App\Models\Category::query();
        $query = $this->applyToQuery($query, new \App\Models\Category());
        
        return $query->get()->toArray();
    }
} 