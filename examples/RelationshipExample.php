<?php

/**
 * Exemplo de uso dos traits de relacionamento
 * 
 * Este arquivo demonstra como usar o sistema de relacionamentos dinâmicos
 * em diferentes cenários do mundo real.
 */

namespace Examples;

use Callcocam\LaraGatekeeper\Core\Concerns\RelationshipManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

// Exemplo 1: Filtro de Produtos
class ProductFilter
{
    use RelationshipManager;

    public function configure(Request $request)
    {
        // Configuração básica
        $this->viaRelationship('category', 'name', 'id');
        
        // Filtros baseados em relacionamentos
        if ($request->filled('category_id')) {
            $this->whereRelationship('category', 'id', $request->category_id);
        }
        
        if ($request->filled('brand_id')) {
            $this->whereRelationship('brand', 'id', $request->brand_id);
        }
        
        if ($request->filled('search')) {
            $this->searchByRelationship('category', 'name', $request->search);
        }
        
        // Filtro customizado para tags
        $this->whereRelationshipCallback('tags', function(Builder $query) use ($request) {
            if ($request->filled('tag_ids')) {
                return $query->whereIn('id', $request->tag_ids);
            }
            return $query;
        });
        
        return $this;
    }
}

// Exemplo 2: Filtro de Usuários
class UserFilter
{
    use RelationshipManager;

    public function configure(Request $request)
    {
        // Múltiplos relacionamentos de uma vez
        $this->filterByMultipleRelationships([
            'department' => [
                'callback' => function(Builder $query) use ($request) {
                    if ($request->filled('department_id')) {
                        return $query->where('id', $request->department_id);
                    }
                    return $query;
                }
            ],
            'role' => [
                'callback' => function(Builder $query) use ($request) {
                    if ($request->filled('role_id')) {
                        return $query->where('id', $request->role_id);
                    }
                    return $query;
                }
            ],
            'permissions' => function(Builder $query) use ($request) {
                if ($request->filled('permission_ids')) {
                    return $query->whereIn('id', $request->permission_ids);
                }
                return $query;
            }
        ]);
        
        return $this;
    }
}

// Exemplo 3: Filtro de Pedidos
class OrderFilter
{
    use RelationshipManager;

    public function configure(Request $request)
    {
        // Relacionamento com filtro complexo
        $this->withRelationship('customer', 'name', 'id', function(Builder $query) use ($request) {
            if ($request->filled('customer_search')) {
                return $query->where('name', 'LIKE', "%{$request->customer_search}%")
                           ->orWhere('email', 'LIKE', "%{$request->customer_search}%");
            }
            return $query;
        });
        
        // Filtro por status do pedido
        $this->whereRelationship('status', 'id', $request->status_id ?? 'pending');
        
        // Filtro por produtos no pedido
        if ($request->filled('product_ids')) {
            $this->whereRelationshipIn('orderItems.product', 'id', $request->product_ids);
        }
        
        return $this;
    }
}

// Exemplo 4: Uso em Controller
class ProductController
{
    public function index(Request $request)
    {
        $filter = new ProductFilter();
        $filter->configure($request);
        
        $query = \App\Models\Product::query();
        
        // Aplica todos os filtros de relacionamento
        $query = $filter->applyToQuery($query, new \App\Models\Product());
        
        // Obtém opções para selects
        $categoryOptions = $filter->getAllRelationshipOptions(new \App\Models\Product());
        
        $products = $query->with(['category', 'brand', 'tags'])->paginate(20);
        
        return view('products.index', compact('products', 'categoryOptions'));
    }
}

// Exemplo 5: Uso em API
class ProductApiController
{
    public function index(Request $request)
    {
        $filter = new ProductFilter();
        $filter->configure($request);
        
        $query = \App\Models\Product::query();
        $query = $filter->applyToQuery($query, new \App\Models\Product());
        
        $products = $query->with(['category', 'brand', 'tags'])->get();
        
        return response()->json([
            'data' => $products,
            'filters' => $filter->getRelationshipStats(),
            'options' => $filter->getAllRelationshipOptions(new \App\Models\Product())
        ]);
    }
}

// Exemplo 6: Filtro Avançado com Detecção Automática
class AdvancedFilter
{
    use RelationshipManager;

    public function autoConfigure(Model $model)
    {
        // Detecta todos os relacionamentos disponíveis
        $relationships = $this->detectRelationships($model);
        
        foreach ($relationships as $relationship) {
            $type = $this->getRelationshipType($model, $relationship);
            
            // Configura automaticamente baseado no tipo
            switch ($type) {
                case 'Illuminate\Database\Eloquent\Relations\BelongsTo':
                    $this->viaRelationship($relationship, 'name', 'id');
                    break;
                    
                case 'Illuminate\Database\Eloquent\Relations\HasMany':
                    // Para relacionamentos hasMany, cria filtros de contagem
                    $this->whereRelationshipCallback($relationship, function(Builder $query) {
                        return $query->where('active', true);
                    });
                    break;
            }
        }
        
        return $this;
    }
} 