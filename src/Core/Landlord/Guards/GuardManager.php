<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Landlord\Guards;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Callcocam\LaraGatekeeper\Core\Landlord\TenantManager;
use Callcocam\LaraGatekeeper\Models\Tenant;

class GuardManager
{
    protected TenantManager $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Obter o guard landlord
     *
     * @return LandlordGuard
     */
    public function landlord(): LandlordGuard
    {
        return Auth::guard('landlord');
    }

    /**
     * Obter o guard tenant
     *
     * @return TenantGuard
     */
    public function tenant(): TenantGuard
    {
        return Auth::guard('tenant');
    }

    /**
     * Verificar se o contexto atual é landlord
     *
     * @return bool
     */
    public function isLandlordContext(): bool
    {
        return Session::get('current_context') === 'landlord';
    }

    /**
     * Verificar se o contexto atual é tenant
     *
     * @return bool
     */
    public function isTenantContext(): bool
    {
        return Session::get('current_context') === 'tenant';
    }

    /**
     * Obter o contexto atual
     *
     * @return string|null
     */
    public function getCurrentContext(): ?string
    {
        return Session::get('current_context');
    }

    /**
     * Obter o guard ativo baseado no contexto
     *
     * @return LandlordGuard|TenantGuard|null
     */
    public function getActiveGuard()
    {
        $context = $this->getCurrentContext();
        
        return match ($context) {
            'landlord' => $this->landlord(),
            'tenant' => $this->tenant(),
            default => null,
        };
    }

    /**
     * Verificar se algum guard está autenticado
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->landlord()->check() || $this->tenant()->check();
    }

    /**
     * Obter o usuário autenticado de qualquer guard
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function getAuthenticatedUser()
    {
        if ($this->landlord()->check()) {
            return $this->landlord()->user();
        }

        if ($this->tenant()->check()) {
            return $this->tenant()->user();
        }

        return null;
    }

    /**
     * Fazer logout de todos os guards
     *
     * @return void
     */
    public function logoutAll(): void
    {
        if ($this->landlord()->check()) {
            $this->landlord()->logout();
        }

        if ($this->tenant()->check()) {
            $this->tenant()->logout();
        }

        // Limpar sessão completamente
        Session::forget('current_context');
        Session::forget('current_tenant');
        Session::forget('current_tenant_id');
        Session::forget('landlord_user');
        Session::forget('tenant_user');
        Session::forget('landlord_impersonating_tenant');

        Log::info('Logout completo realizado');
    }

    /**
     * Alternar do tenant para landlord (apenas se for possível)
     *
     * @return bool
     */
    public function switchToLandlord(): bool
    {
        if (!$this->tenant()->check()) {
            return false;
        }

        $user = $this->tenant()->user();
        
        // Verificar se o usuário pode ser landlord
        if (method_exists($user, 'hasRole')) {
            if (!$user->hasRole('landlord') && !$user->hasRole('super-admin')) {
                return false;
            }
        }

        // Fazer logout do tenant
        $this->tenant()->logout();

        // Fazer login como landlord
        $result = $this->landlord()->loginUsingId($user->getAuthIdentifier());

        if ($result) {
            Log::info('Usuário alternado para contexto landlord', [
                'user_id' => $user->getAuthIdentifier(),
                'email' => $user->email,
            ]);
        }

        return $result;
    }

    /**
     * Alternar do landlord para tenant específico
     *
     * @param  int  $tenantId
     * @return bool
     */
    public function switchToTenant(int $tenantId): bool
    {
        if (!$this->landlord()->check()) {
            return false;
        }

        $landlordUser = $this->landlord()->user();
        
        // Verificar se o landlord pode acessar o tenant
        if (!$this->landlord()->canAccessTenant($tenantId)) {
            return false;
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant || $tenant->status !== 'active') {
            return false;
        }

        // Fazer logout do landlord
        $this->landlord()->logout();

        // Configurar tenant
        $this->tenantManager->enable();
        $this->tenantManager->addTenant('tenant_id', $tenantId);

        // Fazer login como tenant
        $result = $this->tenant()->loginUsingId($landlordUser->getAuthIdentifier());

        if ($result) {
            // Configurar tenant atual
            $this->tenant()->setCurrentTenant($tenant);
            
            Log::info('Landlord alternado para contexto tenant', [
                'user_id' => $landlordUser->getAuthIdentifier(),
                'tenant_id' => $tenantId,
                'tenant_name' => $tenant->name,
            ]);
        }

        return $result;
    }

    /**
     * Impersonar tenant (landlord continua logado)
     *
     * @param  int  $tenantId
     * @return bool
     */
    public function impersonateTenant(int $tenantId): bool
    {
        if (!$this->landlord()->check()) {
            return false;
        }

        return $this->landlord()->impersonateTenant($tenantId);
    }

    /**
     * Parar impersonation de tenant
     *
     * @return bool
     */
    public function stopImpersonation(): bool
    {
        if (!$this->landlord()->check()) {
            return false;
        }

        return $this->landlord()->stopTenantImpersonation();
    }

    /**
     * Verificar se está impersonando tenant
     *
     * @return bool
     */
    public function isImpersonating(): bool
    {
        return $this->landlord()->check() && $this->landlord()->isImpersonatingTenant();
    }

    /**
     * Obter dados da impersonation atual
     *
     * @return array|null
     */
    public function getImpersonationData(): ?array
    {
        if (!$this->isImpersonating()) {
            return null;
        }

        return $this->landlord()->getImpersonationData();
    }

    /**
     * Obter tenant atual (de qualquer contexto)
     *
     * @return Tenant|null
     */
    public function getCurrentTenant(): ?Tenant
    {
        // Se está no contexto tenant
        if ($this->isTenantContext() && $this->tenant()->check()) {
            return $this->tenant()->getCurrentTenant();
        }

        // Se está impersonando tenant
        if ($this->isImpersonating()) {
            $impersonationData = $this->getImpersonationData();
            if ($impersonationData && isset($impersonationData['tenant_id'])) {
                return Tenant::find($impersonationData['tenant_id']);
            }
        }

        return null;
    }

    /**
     * Obter informações completas do estado atual
     *
     * @return array
     */
    public function getState(): array
    {
        $state = [
            'current_context' => $this->getCurrentContext(),
            'is_authenticated' => $this->isAuthenticated(),
            'is_landlord_context' => $this->isLandlordContext(),
            'is_tenant_context' => $this->isTenantContext(),
            'is_impersonating' => $this->isImpersonating(),
            'current_tenant' => null,
            'authenticated_user' => null,
            'landlord_stats' => [],
            'tenant_stats' => [],
        ];

        if ($this->isAuthenticated()) {
            $state['authenticated_user'] = [
                'id' => $this->getAuthenticatedUser()->getAuthIdentifier(),
                'name' => $this->getAuthenticatedUser()->name,
                'email' => $this->getAuthenticatedUser()->email,
            ];
        }

        if ($this->landlord()->check()) {
            $state['landlord_stats'] = $this->landlord()->getLandlordStats();
        }

        if ($this->tenant()->check()) {
            $state['tenant_stats'] = $this->tenant()->getTenantStats();
        }

        $currentTenant = $this->getCurrentTenant();
        if ($currentTenant) {
            $state['current_tenant'] = [
                'id' => $currentTenant->id,
                'name' => $currentTenant->name,
                'slug' => $currentTenant->slug,
                'domain' => $currentTenant->domain,
                'status' => $currentTenant->status,
            ];
        }

        if ($this->isImpersonating()) {
            $state['impersonation_data'] = $this->getImpersonationData();
        }

        return $state;
    }

    /**
     * Verificar se o usuário atual pode executar uma ação
     *
     * @param  string  $action
     * @param  mixed  $resource
     * @return bool
     */
    public function can(string $action, $resource = null): bool
    {
        $activeGuard = $this->getActiveGuard();
        
        if (!$activeGuard || !$activeGuard->check()) {
            return false;
        }

        $user = $activeGuard->user();

        // Verificar através de gates do Laravel
        if (method_exists($user, 'can')) {
            return $user->can($action, $resource);
        }

        // Verificar através de permissões específicas do guard
        if ($activeGuard instanceof TenantGuard) {
            return $activeGuard->canPerformAction($action);
        }

        if ($activeGuard instanceof LandlordGuard) {
            // Landlord pode fazer tudo por padrão
            return true;
        }

        return false;
    }

    /**
     * Limpar todos os caches relacionados aos guards
     *
     * @return void
     */
    public function clearAllCaches(): void
    {
        if ($this->landlord()->check()) {
            // Limpar cache do landlord (se existir método)
            if (method_exists($this->landlord(), 'clearLandlordCache')) {
                $this->landlord()->clearLandlordCache();
            }
        }

        if ($this->tenant()->check()) {
            $this->tenant()->clearTenantCache();
        }

        Log::info('Todos os caches dos guards foram limpos');
    }
} 