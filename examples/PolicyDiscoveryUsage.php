<?php

/**
 * Exemplos práticos de uso da descoberta automática de policies
 * 
 * Demonstra todas as formas de configurar e usar policies:
 * 1. Policy explícita (manual)
 * 2. Descoberta por modelo
 * 3. Descoberta automática pelo item
 * 4. Métodos de conveniência
 */

namespace Callcocam\LaraGatekeeper\Examples;

use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToVisible;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToPermission;
use Callcocam\LaraGatekeeper\Core\Support\VisibilityValidator;

// ============================================================================
// EXEMPLO 1: TODAS AS FORMAS DE DEFINIR POLICIES
// ============================================================================

class PolicyExamplesShowcase
{
    public function demonstrateAllPolicyMethods()
    {
        $product = new \stdClass(); // Simula um produto
        
        // 1. POLICY EXPLÍCITA (como antes)
        $explicit = VisibilityValidator::check($product)
            ->policy('App\\Policies\\ProductPolicy', 'view')
            ->isVisible();
            
        // 2. DESCOBERTA POR MODELO
        $byModel = VisibilityValidator::check($product)
            ->policyFor('App\\Models\\Product', 'view')
            ->isVisible();
            
        // 3. DESCOBERTA AUTOMÁTICA PELO ITEM
        $autoDiscovery = VisibilityValidator::check($product)
            ->autoPolicy('view')
            ->isVisible();
            
        // 4. MÉTODOS DE CONVENIÊNCIA ESTÁTICOS
        $canView = VisibilityValidator::canView($product);
        $canEdit = VisibilityValidator::canEdit($product);
        $canDelete = VisibilityValidator::canDelete($product);
        $canCreate = VisibilityValidator::canCreate('App\\Models\\Product');
        
        return [
            'explicit' => $explicit,
            'by_model' => $byModel,
            'auto_discovery' => $autoDiscovery,
            'can_view' => $canView,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_create' => $canCreate,
        ];
    }
}

// ============================================================================
// EXEMPLO 2: CONTROLLER COM DESCOBERTA AUTOMÁTICA
// ============================================================================

/**
 * Controller que usa descoberta automática de policies
 */
class SmartProductController
{
    use BelongsToVisible;
    
    public function __construct()
    {
        // Configuração base usando descoberta automática
        $this->autoPolicy('viewAny')
             ->requiresPermissions('access-products');
    }
    
    public function index()
    {
        if (!$this->isVisible()) {
            abort(403, 'Sem permissão para visualizar produtos');
        }
        
        // Lógica da listagem...
    }
    
    public function show($product)
    {
        // Usa descoberta automática para o produto específico
        if (!VisibilityValidator::canView($product)) {
            abort(404);
        }
        
        return view('products.show', compact('product'));
    }
    
    public function edit($product)
    {
        // Combina validações - descoberta automática + condições
        $canEdit = VisibilityValidator::check($product)
            ->when(fn($item) => !$item->trashed()) // Não pode editar deletados
            ->autoPolicy('update') // Descobre ProductPolicy automaticamente
            ->requiresPermissions('edit-products')
            ->isVisible();
            
        if (!$canEdit) {
            abort(403, 'Sem permissão para editar este produto');
        }
        
        return view('products.edit', compact('product'));
    }
    
    public function destroy($product)
    {
        // Método de conveniência + validações adicionais
        if (!VisibilityValidator::canDelete($product)) {
            abort(403, 'Sem permissão para deletar');
        }
        
        // Validação adicional: só pode deletar se for o criador OU admin
        if (!VisibilityValidator::ownerOnly($product)->isVisible() && 
            !VisibilityValidator::hasPermission('admin-access')) {
            abort(403, 'Apenas o criador ou admin pode deletar');
        }
        
        $product->delete();
        return redirect()->route('products.index');
    }
}

// ============================================================================
// EXEMPLO 3: FIELD COM MÚLTIPLAS VALIDAÇÕES
// ============================================================================

/**
 * Campo que combina descoberta automática com regras de negócio
 */
class SmartProductStatusField
{
    use BelongsToVisible;
    
    public function __construct()
    {
        // Combina múltiplas validações
        $this->visibleWhen(function($product, $user) {
                // Regras de negócio específicas
                return $product && 
                       !$product->trashed() && 
                       ($product->status !== 'archived');
             })
             ->autoPolicy('update') // Descobre policy automaticamente
             ->requiresPermissions('edit-product-status');
    }
    
    public function render($product)
    {
        if (!$this->isVisible($product)) {
            return null;
        }
        
        // Verifica permissões específicas para diferentes ações
        $canChangeStatus = VisibilityValidator::check($product)
            ->autoPolicy('updateStatus')
            ->isVisible();
            
        $canArchive = VisibilityValidator::check($product)
            ->when(fn($item, $user) => $user->hasRole('admin'))
            ->autoPolicy('archive')
            ->isVisible();
        
        return view('fields.product-status', [
            'product' => $product,
            'can_change_status' => $canChangeStatus,
            'can_archive' => $canArchive
        ]);
    }
}

// ============================================================================
// EXEMPLO 4: SERVICE COM VALIDAÇÕES COMPLEXAS
// ============================================================================

/**
 * Serviço que usa descoberta automática para diferentes modelos
 */
class SmartAuthorizationService
{
    /**
     * Verifica se usuário pode acessar qualquer recurso
     */
    public function canAccessResource($resource, string $action = 'view'): bool
    {
        return VisibilityValidator::check($resource)
            ->autoPolicy($action)
            ->isVisible();
    }
    
    /**
     * Retorna ações disponíveis para um recurso
     */
    public function getAvailableActions($resource): array
    {
        $actions = [];
        
        $actionMap = [
            'view' => 'Visualizar',
            'update' => 'Editar', 
            'delete' => 'Deletar',
            'publish' => 'Publicar',
            'archive' => 'Arquivar'
        ];
        
        foreach ($actionMap as $action => $label) {
            if (VisibilityValidator::check($resource)->autoPolicy($action)->isVisible()) {
                $actions[$action] = $label;
            }
        }
        
        return $actions;
    }
    
    /**
     * Verifica permissões específicas por contexto
     */
    public function checkContextualPermissions($resource, string $context): bool
    {
        return match($context) {
            'business_hours' => VisibilityValidator::businessHours()
                ->autoPolicy('view')
                ->isVisible(),
                
            'owner_only' => VisibilityValidator::ownerOnly($resource)
                ->autoPolicy('update')
                ->isVisible(),
                
            'active_only' => VisibilityValidator::activeOnly($resource)
                ->autoPolicy('view')
                ->isVisible(),
                
            'admin_hours' => VisibilityValidator::check($resource)
                ->when(function() {
                    // Admins podem acessar 24/7, outros só em horário comercial
                    return VisibilityValidator::hasPermission('admin-access') ||
                           (now()->hour >= 8 && now()->hour <= 18);
                })
                ->autoPolicy('view')
                ->isVisible(),
                
            default => false
        };
    }
}

// ============================================================================
// EXEMPLO 5: MIDDLEWARE COM DESCOBERTA AUTOMÁTICA
// ============================================================================

/**
 * Middleware que usa descoberta automática para diferentes recursos
 */
class SmartResourceMiddleware
{
    public function handle($request, \Closure $next, string $resource, string $action = 'view')
    {
        // Pega o parâmetro da rota (ex: {product}, {user})
        $item = $request->route($resource);
        
        if (!$item) {
            // Se não tem item específico, valida pelo modelo
            $modelClass = $this->resolveModelClass($resource);
            $hasAccess = VisibilityValidator::check()
                ->policyFor($modelClass, $action)
                ->isVisible();
        } else {
            // Tem item específico, usa descoberta automática
            $hasAccess = VisibilityValidator::check($item)
                ->autoPolicy($action)
                ->isVisible();
        }
        
        if (!$hasAccess) {
            abort(403, "Sem permissão para {$action} este {$resource}");
        }
        
        return $next($request);
    }
    
    private function resolveModelClass(string $resource): string
    {
        return match($resource) {
            'product' => 'App\\Models\\Product',
            'user' => 'App\\Models\\User',
            'order' => 'App\\Models\\Order',
            default => throw new \InvalidArgumentException("Recurso {$resource} não mapeado")
        };
    }
}

// ============================================================================
// EXEMPLO 6: COMPONENT HELPER INTELIGENTE
// ============================================================================

/**
 * Helper para componentes Vue/Blade com descoberta automática
 */
class SmartComponentHelper
{
    /**
     * Gera dados de autorização para componentes
     */
    public static function getResourcePermissions($resource): array
    {
        $permissions = [
            'can_view' => VisibilityValidator::canView($resource),
            'can_edit' => VisibilityValidator::canEdit($resource), 
            'can_delete' => VisibilityValidator::canDelete($resource),
        ];
        
        // Adiciona permissões específicas do modelo
        $modelName = class_basename($resource);
        $specificPermissions = [
            'publish' => "publish-{$modelName}",
            'archive' => "archive-{$modelName}",
            'export' => "export-{$modelName}",
        ];
        
        foreach ($specificPermissions as $action => $permission) {
            $permissions["can_{$action}"] = VisibilityValidator::check($resource)
                ->autoPolicy($action)
                ->requiresPermissions($permission)
                ->isVisible();
        }
        
        return $permissions;
    }
    
    /**
     * Gera configuração de botões baseada em permissões
     */
    public static function getActionButtons($resource): array
    {
        $buttons = [];
        
        if (VisibilityValidator::canView($resource)) {
            $buttons[] = [
                'action' => 'view',
                'label' => 'Ver',
                'icon' => 'eye',
                'variant' => 'outline'
            ];
        }
        
        if (VisibilityValidator::canEdit($resource)) {
            $buttons[] = [
                'action' => 'edit',
                'label' => 'Editar',
                'icon' => 'edit',
                'variant' => 'primary'
            ];
        }
        
        if (VisibilityValidator::canDelete($resource)) {
            $buttons[] = [
                'action' => 'delete',
                'label' => 'Deletar',
                'icon' => 'trash',
                'variant' => 'danger',
                'confirm' => true
            ];
        }
        
        return $buttons;
    }
}

// ============================================================================
// EXEMPLO 7: BLADE TEMPLATE HELPER
// ============================================================================

/*
<!-- resources/views/partials/smart-actions.blade.php -->

@php
use Callcocam\LaraGatekeeper\Core\Support\VisibilityValidator;
use Callcocam\LaraGatekeeper\Examples\SmartComponentHelper;

$permissions = SmartComponentHelper::getResourcePermissions($item);
$buttons = SmartComponentHelper::getActionButtons($item);
@endphp

<div class="resource-actions">
    @foreach($buttons as $button)
        <button 
            class="btn btn-{{ $button['variant'] }}"
            @if($button['confirm'] ?? false)
                onclick="return confirm('Tem certeza?')"
            @endif
        >
            <i class="icon-{{ $button['icon'] }}"></i>
            {{ $button['label'] }}
        </button>
    @endforeach
    
    <!-- Ações específicas com descoberta automática -->
    @if(VisibilityValidator::check($item)->autoPolicy('publish')->isVisible())
        <button class="btn btn-success">Publicar</button>
    @endif
    
    @if(VisibilityValidator::activeOnly($item)->autoPolicy('archive')->isVisible())
        <button class="btn btn-warning">Arquivar</button>
    @endif
    
    <!-- Validações contextuais -->
    @if(VisibilityValidator::businessHours()->ownerOnly($item)->isVisible())
        <button class="btn btn-info">Edição Rápida</button>
    @endif
</div>
*/

// ============================================================================
// RESUMO DAS VANTAGENS DA DESCOBERTA AUTOMÁTICA:
// ============================================================================

/*
🚀 BENEFÍCIOS PRINCIPAIS:

1. **ZERO CONFIGURAÇÃO para casos padrão:**
   - Product → App\Policies\ProductPolicy automaticamente
   - User → App\Policies\UserPolicy automaticamente
   
2. **MÚLTIPLAS CONVENÇÕES suportadas:**
   - App\Policies\ProductPolicy (padrão)
   - App\Policies\Product (sem sufixo)
   - App\Http\Policies\ProductPolicy (subpasta)
   - App\Domain\Policies\ProductPolicy (DDD)
   
3. **FLEXIBILIDADE TOTAL:**
   - Policy explícita quando necessário
   - Descoberta por modelo quando conhece a classe
   - Descoberta automática quando tem o item
   
4. **MÉTODOS DE CONVENIÊNCIA:**
   - canView($item), canEdit($item), canDelete($item)
   - businessHours(), ownerOnly($item), activeOnly($item)
   
5. **COMPATIBILIDADE 100%:**
   - Todo código existente continua funcionando
   - Pode migrar gradualmente para descoberta automática
   
6. **LOGS DE DEBUG:**
   - Sistema loga quando não encontra policies
   - Mostra todas as convenções tentadas
   
7. **PERFORMANCE:**
   - Descoberta feita apenas uma vez por validação
   - Cache de class_exists() do PHP nativo
*/ 