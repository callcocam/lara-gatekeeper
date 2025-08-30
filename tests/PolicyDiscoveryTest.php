<?php

namespace Callcocam\LaraGatekeeper\Tests;

use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToPermission;
use Callcocam\LaraGatekeeper\Core\Support\VisibilityValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PolicyDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_explicit_policy_definition_works()
    {
        $testClass = new class {
            use BelongsToPermission;
            
            public function __construct()
            {
                // Policy explícita - não usa descoberta automática
                $this->policy('App\\Policies\\ExplicitPolicy', 'view');
            }
        };

        // Verifica que descoberta automática foi desabilitada
        $this->assertFalse($testClass->getAutoDiscoverPolicy());
        $this->assertEquals('App\\Policies\\ExplicitPolicy', $testClass->getPolicyClass());
    }

    /** @test */
    public function test_policy_for_model_discovery()
    {
        $testClass = new class {
            use BelongsToPermission;
            
            // Mock do método discoverPolicyClass para testes
            protected function discoverPolicyClass(string $modelClass): ?string
            {
                if ($modelClass === 'App\\Models\\Product') {
                    return 'App\\Policies\\ProductPolicy';
                }
                return null;
            }
        };

        // Testa policyFor com modelo específico
        $testClass->policyFor('App\\Models\\Product', 'update');

        $this->assertEquals('App\\Policies\\ProductPolicy', $testClass->getPolicyClass());
        $this->assertEquals('update', $testClass->getPolicyMethod());
        $this->assertFalse($testClass->getAutoDiscoverPolicy());
    }

    /** @test */
    public function test_auto_policy_discovery()
    {
        $testClass = new class {
            use BelongsToPermission;
            
            public function __construct()
            {
                // Habilita descoberta automática
                $this->autoPolicy('delete');
            }
        };

        // Verifica que descoberta automática está habilitada
        $this->assertTrue($testClass->getAutoDiscoverPolicy());
        $this->assertEquals('delete', $testClass->getPolicyMethod());
    }

    /** @test */
    public function test_policy_class_discovery_conventions()
    {
        $testClass = new class {
            use BelongsToPermission;
            
            // Simula que as classes existem
            protected function discoverPolicyClass(string $modelClass): ?string
            {
                $modelName = class_basename($modelClass);
                
                // Simula diferentes convenções
                $conventions = [
                    "App\\Policies\\{$modelName}Policy",
                    "App\\Policies\\{$modelName}",
                    "App\\Http\\Policies\\{$modelName}Policy",
                ];
                
                foreach ($conventions as $convention) {
                    // Simula que ProductPolicy existe
                    if ($convention === 'App\\Policies\\ProductPolicy' && $modelName === 'Product') {
                        return $convention;
                    }
                    // Simula que UserPolicy não tem sufixo
                    if ($convention === 'App\\Policies\\User' && $modelName === 'User') {
                        return $convention;
                    }
                }
                
                return null;
            }
        };

        // Testa convenção padrão (ProductPolicy)
        $this->assertEquals(
            'App\\Policies\\ProductPolicy',
            $testClass->policyFor('App\\Models\\Product')->getPolicyClass()
        );

        // Testa convenção sem sufixo (User)
        $this->assertEquals(
            'App\\Policies\\User',
            $testClass->policyFor('App\\Models\\User')->getPolicyClass()
        );
    }

    /** @test */
    public function test_visibility_validator_with_policy_discovery()
    {
        $product = new class {
            public $id = 1;
            public $status = 'active';
        };

        $user = new class {
            public $id = 123;
            public function can($permission) { return true; }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Testa método canView (descoberta automática)
        $validator = VisibilityValidator::check($product)->autoPolicy('view');
        $this->assertTrue($validator->getAutoDiscoverPolicy());

        // Testa métodos estáticos de conveniência
        $this->assertTrue(VisibilityValidator::canView($product));
        $this->assertTrue(VisibilityValidator::canEdit($product));
        $this->assertTrue(VisibilityValidator::canDelete($product));
    }

    /** @test */
    public function test_business_logic_convenience_methods()
    {
        $activeItem = new class {
            public $status = 'active';
            public $user_id = 123;
        };

        $inactiveItem = new class {
            public $status = 'inactive';
            public $user_id = 456;
        };

        $user = new class {
            public $id = 123;
            public function can($permission) { return true; }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Testa activeOnly
        $this->assertTrue(
            VisibilityValidator::activeOnly($activeItem)->isVisible()
        );
        
        $this->assertFalse(
            VisibilityValidator::activeOnly($inactiveItem)->isVisible()
        );

        // Testa ownerOnly
        $this->assertTrue(
            VisibilityValidator::ownerOnly($activeItem)->isVisible()
        );
        
        $this->assertFalse(
            VisibilityValidator::ownerOnly($inactiveItem)->isVisible()
        );
    }

    /** @test */
    public function test_combined_validations_with_auto_discovery()
    {
        $product = new class {
            public $status = 'active';
            public $user_id = 123;
        };

        $user = new class {
            public $id = 123;
            public function can($permission) { return true; }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Combina várias validações
        $canAccess = VisibilityValidator::check($product)
            ->when(fn($item, $user) => $item->status === 'active') // Condição personalizada
            ->autoPolicy('update') // Descoberta automática de policy
            ->requiresPermissions('edit-products') // Permissões específicas
            ->isVisible();

        $this->assertTrue($canAccess);

        // Testa quando condição personalizada falha
        $product->status = 'inactive';
        
        $cannotAccess = VisibilityValidator::check($product)
            ->when(fn($item, $user) => $item->status === 'active') // Falha aqui
            ->autoPolicy('update')
            ->requiresPermissions('edit-products')
            ->isVisible();

        $this->assertFalse($cannotAccess);
    }

    /** @test */
    public function test_business_hours_validation()
    {
        $user = new class {
            public function can($permission) { return true; }
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        // Mock do now() para simular horário comercial
        $this->travelTo(now()->setTime(10, 0)); // 10:00 AM

        $validator = VisibilityValidator::businessHours();
        
        // Durante horário comercial deve passar
        $this->assertTrue($validator->isVisible());

        // Mock para fora do horário comercial
        $this->travelTo(now()->setTime(20, 0)); // 8:00 PM

        $validator = VisibilityValidator::businessHours();
        
        // Fora do horário comercial deve falhar
        $this->assertFalse($validator->isVisible());
    }

    /** @test */
    public function test_policy_discovery_with_resolve_policy_class()
    {
        $testClass = new class {
            use BelongsToPermission;
            
            protected function discoverPolicyClass(string $modelClass): ?string
            {
                if (str_contains($modelClass, 'Product')) {
                    return 'App\\Policies\\ProductPolicy';
                }
                return null;
            }
        };

        $product = new class {
            // Simula um produto
        };

        // Habilita descoberta automática
        $testClass->autoPolicy('view');

        // Mock do método resolvePolicyClass para testar
        $reflection = new \ReflectionClass($testClass);
        $method = $reflection->getMethod('resolvePolicyClass');
        $method->setAccessible(true);

        // Testa resolução com item
        $resolvedPolicy = $method->invoke($testClass, $product);
        $this->assertEquals('App\\Policies\\ProductPolicy', $resolvedPolicy);

        // Testa resolução sem item
        $resolvedPolicyNull = $method->invoke($testClass, null);
        $this->assertNull($resolvedPolicyNull);
    }
} 