<?php

/**
 * Exemplo prático de uso do sistema refatorado de visibilidade
 * 
 * Demonstra como usar os traits com responsabilidades separadas:
 * - BelongsToPermission: Lógica de autenticação, policies e permissões
 * - BelongsToVisible: Lógica de visibilidade e condições personalizadas
 * 
 * NOTA: Este arquivo contém apenas exemplos. Classes como App\Policies\ProductPolicy,
 * App\Models\Product, etc. são usadas como referência e devem ser adaptadas
 * para seu projeto específico.
 */

namespace Callcocam\LaraGatekeeper\Examples;

use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToVisible;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToPermission;
use Callcocam\LaraGatekeeper\Core\Support\VisibilityValidator;

// ============================================================================
// EXEMPLO 1: Action usando apenas BelongsToPermission
// ============================================================================

/**
 * Action simples que só precisa de validações de permissão
 */
class ProductDeleteAction
{
    use BelongsToPermission;
    
    public function __construct()
    {
        // Configura apenas permissões - sem condições de visibilidade
        // @phpstan-ignore-next-line - Exemplo de uso, classe não existe neste contexto
        $this->policy(\App\Policies\ProductPolicy::class, 'delete')
             ->requiresPermissions('delete-products')
             ->requiresAuth();
    }
    
    public function execute($product)
    {
        // Verifica se usuário tem permissão para executar a action
        if (!$this->validatePermissions($product)) {
            abort(403, 'Sem permissão para deletar este produto');
        }
        
        $product->delete();
        return response()->json(['message' => 'Produto deletado com sucesso']);
    }
}

// ============================================================================
// EXEMPLO 2: Field usando BelongsToVisible (que inclui BelongsToPermission)
// ============================================================================

/**
 * Campo que combina condições de visibilidade + permissões
 */
class ProductStatusField
{
    use BelongsToVisible;
    
    public function __construct()
    {
        // Combina condições personalizadas com permissões
        $this->visibleWhen(function($product, $user) {
                // Só aparece se produto não estiver arquivado E
                // se for o criador do produto OU admin
                return !$product->trashed() && 
                       ($product->user_id === $user->id || $user->hasRole('admin'));
             })
             ->requiresPermissions('edit-product-status')
             ->policy(\App\Policies\ProductPolicy::class, 'update');
    }
    
    public function render($product)
    {
        // Usa isVisible que combina visibilidade + permissões
        if (!$this->isVisible($product)) {
            return null; // Campo não aparece
        }
        
        return view('fields.product-status', [
            'product' => $product,
            'can_edit' => $this->validatePermissions($product)
        ]);
    }
}

// ============================================================================
// EXEMPLO 3: Menu Service usando VisibilityValidator
// ============================================================================

/**
 * Serviço de menu que usa VisibilityValidator para construir navegação
 */
class MenuService
{
    public function getAdminMenuItems()
    {
        $items = [];
        
        // Item básico - só permissão
        if (VisibilityValidator::hasPermission('view-products')) {
            $items[] = [
                'label' => 'Produtos',
                'route' => 'products.index',
                'icon' => 'package'
            ];
        }
        
        // Item com policy específica
        if (VisibilityValidator::can('viewAny', \App\Models\Report::class)) {
            $items[] = [
                'label' => 'Relatórios',
                'route' => 'reports.index',
                'icon' => 'chart-bar'
            ];
        }
        
        // Item com condição personalizada + permissões
        if (VisibilityValidator::check()
                ->when(fn() => now()->hour >= 9 && now()->hour <= 18) // Horário comercial
                ->requiresPermissions(['view-financial', 'access-sensitive-data'])
                ->isVisible()) {
            $items[] = [
                'label' => 'Financeiro',
                'route' => 'financial.index',
                'icon' => 'dollar-sign'
            ];
        }
        
        // Item que usa métodos de conveniência
        if (VisibilityValidator::withPermissions(['admin-access', 'super-user'])->isVisible()) {
            $items[] = [
                'label' => 'Configurações',
                'route' => 'admin.settings',
                'icon' => 'settings'
            ];
        }
        
        return $items;
    }
}

// ============================================================================
// EXEMPLO 4: Controller usando ambos os traits
// ============================================================================

/**
 * Controller que demonstra uso prático em diferentes cenários
 */
class ProductController
{
    use BelongsToVisible;
    
    public function __construct()
    {
        // Configuração base para todo o controller
        $this->policy(\App\Policies\ProductPolicy::class, 'viewAny')
             ->requiresPermissions('access-products');
    }
    
    public function index()
    {
        // Verifica se usuário pode acessar a listagem
        if (!$this->isVisible()) {
            abort(403, 'Sem permissão para visualizar produtos');
        }
        
        $products = Product::paginate(15);
        
        return view('products.index', compact('products'));
    }
    
    public function show(Product $product)
    {
        // Validação específica para visualização de produto
        $canView = VisibilityValidator::check($product)
            ->when(function($product, $user) {
                // Regra de negócio: só pode ver produtos ativos OU se for o criador
                return $product->status === 'active' || $product->user_id === $user->id;
            })
            ->policy(\App\Policies\ProductPolicy::class, 'view')
            ->isVisible();
            
        if (!$canView) {
            abort(404); // Simula que produto não existe
        }
        
        return view('products.show', compact('product'));
    }
    
    public function edit(Product $product)
    {
        // Combina validação base + condições específicas para edição
        $canEdit = $this->isVisible($product) && // Validação base do controller
                   VisibilityValidator::check($product)
                       ->when(fn($product) => !$product->trashed()) // Não pode editar deletados
                       ->policy(\App\Policies\ProductPolicy::class, 'update')
                       ->requiresPermissions('edit-products')
                       ->isVisible();
                       
        if (!$canEdit) {
            abort(403, 'Sem permissão para editar este produto');
        }
        
        return view('products.edit', compact('product'));
    }
}

// ============================================================================
// EXEMPLO 5: Component Vue/Blade Helper
// ============================================================================

/**
 * Helper para componentes que precisam verificar visibilidade
 */
class ComponentVisibilityHelper
{
    /**
     * Verifica se botão de ação deve aparecer
     */
    public static function canShowActionButton($action, $item = null): bool
    {
        return match($action) {
            'edit' => VisibilityValidator::check($item)
                ->when(fn($item) => $item && !$item->trashed())
                ->requiresPermissions('edit-' . class_basename($item))
                ->isVisible(),
                
            'delete' => VisibilityValidator::check($item)
                ->when(fn($item, $user) => $item && ($item->user_id === $user->id || $user->hasRole('admin')))
                ->requiresPermissions('delete-' . class_basename($item))
                ->isVisible(),
                
            'publish' => VisibilityValidator::check($item)
                ->when(fn($item) => $item && $item->status === 'draft')
                ->requiresPermissions('publish-content')
                ->isVisible(),
                
            default => false
        };
    }
    
    /**
     * Retorna lista de ações disponíveis para um item
     */
    public static function getAvailableActions($item): array
    {
        $actions = [];
        
        foreach (['view', 'edit', 'delete', 'publish'] as $action) {
            if (self::canShowActionButton($action, $item)) {
                $actions[] = $action;
            }
        }
        
        return $actions;
    }
}

// ============================================================================
// EXEMPLO 6: Uso em Blade Templates
// ============================================================================

/*
<!-- resources/views/products/partials/actions.blade.php -->

@php
use Callcocam\LaraGatekeeper\Core\Support\VisibilityValidator;
use Callcocam\LaraGatekeeper\Examples\ComponentVisibilityHelper;
@endphp

<div class="product-actions">
    @if(ComponentVisibilityHelper::canShowActionButton('edit', $product))
        <a href="{{ route('products.edit', $product) }}" class="btn btn-primary">
            Editar
        </a>
    @endif
    
    @if(ComponentVisibilityHelper::canShowActionButton('delete', $product))
        <form method="POST" action="{{ route('products.destroy', $product) }}" class="d-inline">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza?')">
                Deletar
            </button>
        </form>
    @endif
    
    @if(VisibilityValidator::hasPermission('view-product-analytics'))
        <a href="{{ route('products.analytics', $product) }}" class="btn btn-info">
            Analytics
        </a>
    @endif
</div>
*/

// ============================================================================
// EXEMPLO 7: Middleware personalizado usando o sistema
// ============================================================================

/**
 * Middleware que usa o sistema de visibilidade para controle de acesso
 */
class VisibilityMiddleware
{
    public function handle($request, \Closure $next, ...$permissions)
    {
        // Usa VisibilityValidator para verificação complexa
        $hasAccess = VisibilityValidator::check()
            ->when(function() use ($request) {
                // Regra: APIs só funcionam em horário comercial, exceto para admins
                if ($request->is('api/*')) {
                    $now = now();
                    $isBusinessHours = $now->hour >= 8 && $now->hour <= 18;
                    $isAdmin = auth()->user()?->hasRole('admin');
                    
                    return $isBusinessHours || $isAdmin;
                }
                return true;
            })
            ->requiresPermissions($permissions)
            ->requiresAuth()
            ->isVisible();
            
        if (!$hasAccess) {
            abort(403, 'Acesso negado');
        }
        
        return $next($request);
    }
}

// ============================================================================
// VANTAGENS DA ARQUITETURA REFATORADA:
// ============================================================================

/*
1. **Separação de Responsabilidades:**
   - BelongsToPermission: Foca apenas em autenticação, policies e permissões
   - BelongsToVisible: Foca em lógica de visibilidade e condições personalizadas
   
2. **Reutilização:**
   - Pode usar só BelongsToPermission quando não precisa de visibilidade
   - BelongsToVisible automaticamente inclui funcionalidades de permissão
   
3. **Testabilidade:**
   - Cada trait pode ser testado independentemente
   - Mocks mais simples e focados
   
4. **Flexibilidade:**
   - VisibilityValidator pode usar qualquer combinação de validações
   - Fácil extensão para novos tipos de validação
   
5. **Performance:**
   - Validação sequencial (para na primeira que falha)
   - Cache nativo de permissões mantido
   
6. **Manutenibilidade:**
   - Código mais organizado e fácil de entender
   - Cada classe tem responsabilidade única
   - Documentação clara sobre o que cada método faz
*/ 