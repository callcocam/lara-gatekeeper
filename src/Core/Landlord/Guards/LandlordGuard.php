<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Landlord\Guards;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Callcocam\LaraGatekeeper\Core\Landlord\TenantManager;
use Callcocam\LaraGatekeeper\Models\Tenant;

class LandlordGuard extends SessionGuard
{
    protected TenantManager $tenantManager;
    protected array $impersonatingTenant = [];

    public function __construct($name, $provider, Session $session, Request $request = null, TenantManager $tenantManager = null)
    {
        parent::__construct($name, $provider, $session, $request);
        $this->tenantManager = $tenantManager ?? app(TenantManager::class);
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        // Log da tentativa de login landlord
        Log::info('Tentativa de login landlord', [
            'email' => $credentials['email'] ?? 'não informado',
            'ip' => $this->request?->ip(),
            'user_agent' => $this->request?->userAgent(),
        ]);

        $result = parent::attempt($credentials, $remember);

        if ($result) {
            $this->configureLandlordSession();
            
            Log::info('Login landlord bem-sucedido', [
                'user_id' => $this->id(),
                'email' => $this->user()->email,
            ]);
        } else {
            Log::warning('Falha no login landlord', [
                'email' => $credentials['email'] ?? 'não informado',
                'ip' => $this->request?->ip(),
            ]);
        }

        return $result;
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout()
    {
        if ($this->check()) {
            Log::info('Logout landlord', [
                'user_id' => $this->id(),
                'email' => $this->user()->email,
            ]);
        }

        // Limpar sessão de impersonation
        $this->clearTenantImpersonation();
        
        // Limpar sessão landlord
        $this->session->forget('landlord_user');
        $this->session->forget('current_context');
        
        parent::logout();
    }

    /**
     * Configurar sessão específica do landlord
     */
    protected function configureLandlordSession(): void
    {
        if (!$this->check()) {
            return;
        }

        $user = $this->user();
        
        // Desabilitar tenant scopes para landlord
        $this->tenantManager->disable();
        
        // Configurar contexto landlord
        $this->session->put('current_context', 'landlord');
        $this->session->put('landlord_user', [
            'id' => $user->getAuthIdentifier(),
            'name' => $user->name,
            'email' => $user->email,
            'is_landlord' => true,
            'login_at' => now()->toISOString(),
        ]);

        // Limpar qualquer contexto de tenant anterior
        $this->session->forget('current_tenant');
        $this->session->forget('current_tenant_id');
    }

    /**
     * Verificar se o usuário pode acessar um tenant específico
     *
     * @param  int  $tenantId
     * @return bool
     */
    public function canAccessTenant(int $tenantId): bool
    {
        if (!$this->check()) {
            return false;
        }

        $user = $this->user();
        
        // Verificar se é super admin ou landlord
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('super-admin') || $user->hasRole('landlord')) {
                return true;
            }
        }

        // Verificar permissões específicas
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission("tenant.{$tenantId}.access") ||
                   $user->hasPermission('tenant.*.access');
        }

        return true; // Por padrão, landlord pode acessar qualquer tenant
    }

    /**
     * Impersonar um tenant específico
     *
     * @param  int  $tenantId
     * @return bool
     */
    public function impersonateTenant(int $tenantId): bool
    {
        if (!$this->canAccessTenant($tenantId)) {
            return false;
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return false;
        }

        // Habilitar tenant scopes temporariamente
        $this->tenantManager->enable();
        $this->tenantManager->addTenant('tenant_id', $tenantId);

        // Configurar impersonation
        $this->impersonatingTenant = [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenant->name,
            'started_at' => now()->toISOString(),
            'original_context' => 'landlord',
        ];

        $this->session->put('landlord_impersonating_tenant', $this->impersonatingTenant);
        $this->session->put('current_tenant', [
            'id' => $tenant->id,
            'slug' => $tenant->slug,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
        ]);

        Log::info('Landlord iniciou impersonation de tenant', [
            'landlord_user_id' => $this->id(),
            'tenant_id' => $tenantId,
            'tenant_name' => $tenant->name,
        ]);

        return true;
    }

    /**
     * Parar impersonation de tenant
     *
     * @return bool
     */
    public function stopTenantImpersonation(): bool
    {
        if (!$this->isImpersonatingTenant()) {
            return false;
        }

        $impersonationData = $this->session->get('landlord_impersonating_tenant', []);
        
        Log::info('Landlord parou impersonation de tenant', [
            'landlord_user_id' => $this->id(),
            'tenant_id' => $impersonationData['tenant_id'] ?? null,
        ]);

        $this->clearTenantImpersonation();
        $this->configureLandlordSession();

        return true;
    }

    /**
     * Verificar se está impersonando um tenant
     *
     * @return bool
     */
    public function isImpersonatingTenant(): bool
    {
        return $this->session->has('landlord_impersonating_tenant');
    }

    /**
     * Obter dados da impersonation atual
     *
     * @return array|null
     */
    public function getImpersonationData(): ?array
    {
        return $this->session->get('landlord_impersonating_tenant');
    }

    /**
     * Limpar dados de impersonation
     */
    protected function clearTenantImpersonation(): void
    {
        $this->session->forget('landlord_impersonating_tenant');
        $this->session->forget('current_tenant');
        $this->session->forget('current_tenant_id');
        
        // Desabilitar tenant scopes
        $this->tenantManager->disable();
        
        $this->impersonatingTenant = [];
    }

    /**
     * Obter lista de tenants acessíveis
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAccessibleTenants()
    {
        if (!$this->check()) {
            return collect();
        }

        $user = $this->user();
        
        // Se é super admin ou landlord, pode acessar todos
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('super-admin') || $user->hasRole('landlord')) {
                return Tenant::where('status', 'active')->get();
            }
        }

        // Buscar tenants específicos baseado em permissões
        $tenantIds = [];
        if (method_exists($user, 'permissions')) {
            $permissions = $user->permissions()
                ->where('name', 'like', 'tenant.%.access')
                ->pluck('name');

            foreach ($permissions as $permission) {
                if (preg_match('/tenant\.(\d+)\.access/', $permission, $matches)) {
                    $tenantIds[] = (int) $matches[1];
                }
            }
        }

        if (empty($tenantIds)) {
            return collect();
        }

        return Tenant::whereIn('id', $tenantIds)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Obter estatísticas do landlord
     *
     * @return array
     */
    public function getLandlordStats(): array
    {
        if (!$this->check()) {
            return [];
        }

        $cacheKey = "landlord_stats_{$this->id()}";
        
        return Cache::remember($cacheKey, 300, function () { // 5 minutos
            $accessibleTenants = $this->getAccessibleTenants();
            
            return [
                'total_accessible_tenants' => $accessibleTenants->count(),
                'active_tenants' => $accessibleTenants->where('status', 'active')->count(),
                'inactive_tenants' => $accessibleTenants->where('status', 'inactive')->count(),
                'total_users_in_tenants' => $accessibleTenants->sum('users_count'),
                'is_impersonating' => $this->isImpersonatingTenant(),
                'current_impersonation' => $this->getImpersonationData(),
            ];
        });
    }

    /**
     * Verificar se o usuário atual é super admin
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        if (!$this->check()) {
            return false;
        }

        $user = $this->user();
        
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('super-admin');
        }

        return false;
    }

    /**
     * Alternar entre modo debug para landlord
     *
     * @param  bool  $enabled
     * @return void
     */
    public function toggleDebugMode(bool $enabled = true): void
    {
        if (!$this->isSuperAdmin()) {
            return;
        }

        $this->session->put('landlord_debug_mode', $enabled);
        
        Log::info('Landlord debug mode alterado', [
            'user_id' => $this->id(),
            'debug_enabled' => $enabled,
        ]);
    }

    /**
     * Verificar se o modo debug está ativo
     *
     * @return bool
     */
    public function isDebugModeEnabled(): bool
    {
        return $this->session->get('landlord_debug_mode', false);
    }
} 