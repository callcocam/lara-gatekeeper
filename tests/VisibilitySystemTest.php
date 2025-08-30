<?php

namespace Callcocam\LaraGatekeeper\Tests;

use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToVisible;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToPermission;
use Callcocam\LaraGatekeeper\Core\Support\VisibilityValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class VisibilitySystemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_belongs_to_permission_trait_basic_functionality()
    {
        $testClass = new class {
            use BelongsToPermission;
            
            public function __construct()
            {
                $this->requiresPermissions('manage-products')
                     ->requiresAuth(true);
            }
            
            // Mock do método hasPermission para compatibilidade legacy
            public function hasPermission($user = null): bool
            {
                return true; // Simula que tem permissão legacy
            }
        };

        $user = new class {
            public $id = 123;
            public function can($permission) {
                return $permission === 'manage-products';
            }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Teste do trait de permissões
        $this->assertTrue($testClass->validatePermissions());

        // Teste dos getters
        $this->assertEquals(['manage-products'], $testClass->getRequiredPermissions());
        $this->assertTrue($testClass->getRequiresAuthentication());
    }

    /** @test */
    public function test_belongs_to_visible_trait_with_permission_integration()
    {
        $testClass = new class {
            use BelongsToVisible;
            
            public function __construct()
            {
                $this->requiresPermissions('manage-products')
                     ->visibleWhen(fn($item, $user) => $item ? $item->status === 'active' : true);
            }
            
            // Mock do método hasPermission para compatibilidade legacy
            public function hasPermission($user = null): bool
            {
                return true;
            }
            
            // Mock do método evaluate
            public function evaluate($value, array $namedInjections = []): mixed
            {
                if (is_callable($value)) {
                    return call_user_func($value, $namedInjections['item'] ?? null, $namedInjections['user'] ?? null);
                }
                return $value;
            }
        };

        $item = (object) ['status' => 'active'];
        $user = new class {
            public $id = 123;
            public function can($permission) {
                return $permission === 'manage-products';
            }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Teste com item ativo - deve passar
        $this->assertTrue($testClass->isVisible($item));

        // Teste com item inativo - deve falhar na condição personalizada
        $item->status = 'inactive';
        $this->assertFalse($testClass->isVisible($item));
    }

    /** @test */
    public function test_visibility_validator_with_refactored_architecture()
    {
        // Teste sem usuário logado
        Auth::shouldReceive('check')->andReturn(false);
        Auth::shouldReceive('user')->andReturn(null);
        
        $this->assertFalse(
            VisibilityValidator::check()
                ->requiresAuth()
                ->isVisible()
        );

        // Teste permitindo visitantes
        $this->assertTrue(
            VisibilityValidator::check()
                ->allowGuests()
                ->isVisible()
        );
    }

    /** @test */
    public function test_visibility_validator_with_permissions()
    {
        $user = new class {
            public $id = 123;
            public function can($permission) {
                return $permission === 'manage-products';
            }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Teste com permissão válida
        $this->assertTrue(
            VisibilityValidator::check()
                ->requiresPermissions('manage-products')
                ->isVisible()
        );

        // Teste com permissão inválida
        $this->assertFalse(
            VisibilityValidator::check()
                ->requiresPermissions('delete-products')
                ->isVisible()
        );
    }

    /** @test */
    public function test_visibility_validator_with_custom_condition()
    {
        $item = (object) ['status' => 'active', 'is_public' => true];
        
        $user = new class {
            public $id = 123;
            public function can($permission) { return true; }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Teste com condição que retorna true
        $this->assertTrue(
            VisibilityValidator::check($item)
                ->when(fn($item, $user) => $item->status === 'active')
                ->isVisible()
        );

        // Teste com condição que retorna false
        $this->assertFalse(
            VisibilityValidator::check($item)
                ->when(fn($item, $user) => $item->status === 'inactive')
                ->isVisible()
        );
    }

    /** @test */
    public function test_visibility_validator_static_methods()
    {
        $user = new class {
            public function can($permission) {
                return in_array($permission, ['view-products', 'edit-products']);
            }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Teste métodos estáticos
        $this->assertTrue(VisibilityValidator::isAuthenticated());
        $this->assertTrue(VisibilityValidator::hasPermission('view-products'));
        $this->assertFalse(VisibilityValidator::hasPermission('delete-products'));
        
        $this->assertTrue(VisibilityValidator::hasAllPermissions(['view-products', 'edit-products']));
        $this->assertFalse(VisibilityValidator::hasAllPermissions(['view-products', 'delete-products']));
        
        $this->assertTrue(VisibilityValidator::hasAnyPermission(['view-products', 'delete-products']));
        $this->assertFalse(VisibilityValidator::hasAnyPermission(['delete-products', 'admin-access']));
    }

    /** @test */
    public function test_context_specific_visibility_methods()
    {
        $testClass = new class {
            use BelongsToVisible;
            
            public function __construct()
            {
                $this->visibleWhenIndex(false) // Não visível em index
                     ->visibleWhenShow(true)   // Visível em show
                     ->requiresPermissions('view-products');
            }
            
            public function hasPermission($user = null): bool
            {
                return true;
            }
            
            public function evaluate($value, array $namedInjections = []): mixed
            {
                return $value;
            }
        };

        $user = new class {
            public $id = 123;
            public function can($permission) { return true; }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Teste contextos específicos
        $this->assertFalse($testClass->isVisibleOnIndex());
        $this->assertTrue($testClass->isVisibleOnShow());
        $this->assertTrue($testClass->isVisibleOnCreate()); // Sem restrição específica
    }

    /** @test */
    public function test_convenience_methods()
    {
        $user = new class {
            public function can($permission) { return true; }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Teste métodos de conveniência
        $this->assertTrue(
            VisibilityValidator::authenticated()->isVisible()
        );

        $this->assertTrue(
            VisibilityValidator::withPermissions('manage-products')->isVisible()
        );

        // Teste guests
        Auth::shouldReceive('check')->andReturn(false);
        Auth::shouldReceive('user')->andReturn(null);
        
        $this->assertTrue(
            VisibilityValidator::guests()->isVisible()
        );
    }

    /** @test */
    public function test_layered_validation_order_with_separated_responsibilities()
    {
        $item = (object) ['status' => 'inactive'];
        
        $user = new class {
            public $id = 123;
            public function can($permission) { return true; }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Condição personalizada retorna false, deve parar aqui (mesmo com permissões válidas)
        $this->assertFalse(
            VisibilityValidator::check($item)
                ->when(fn($item) => $item->status === 'active') // Retorna false
                ->requiresPermissions('manage-products') // Não deve chegar aqui
                ->isVisible()
        );

        // Condição retorna true, deve continuar para permissões
        $this->assertTrue(
            VisibilityValidator::check($item)
                ->when(fn($item) => $item->status === 'inactive') // Retorna true
                ->requiresPermissions('manage-products') // Deve verificar isso
                ->isVisible()
        );
    }
} 