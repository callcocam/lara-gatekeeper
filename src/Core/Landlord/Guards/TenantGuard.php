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

class TenantGuard extends SessionGuard
{
    protected TenantManager $tenantManager;
    protected ?Tenant $currentTenant = null;

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
        $currentTenant = $this->getCurrentTenant();
        
        // Log da tentativa de login tenant
        Log::info('Tentativa de login tenant', [
            'email' => $credentials['email'] ?? 'não informado',
            'tenant_id' => $currentTenant?->id,
            'tenant_slug' => $currentTenant?->slug,
            'ip' => $this->request?->ip(),
            'user_agent' => $this->request?->userAgent(),
        ]);

        // Verificar se o tenant está ativo
        if (!$currentTenant || $currentTenant->status !== 'active') {
            Log::warning('Tentativa de login em tenant inativo', [
                'tenant_id' => $currentTenant?->id,
                'tenant_status' => $currentTenant?->status,
            ]);
            return false;
        }

        // Configurar scope de tenant antes da autenticação
        $this->configureTenantScope();

        $result = parent::attempt($credentials, $remember);

        if ($result) {
            $this->configureTenantSession();
            
            Log::info('Login tenant bem-sucedido', [
                'user_id' => $this->id(),
                'email' => $this->user()->email,
                'tenant_id' => $currentTenant->id,
            ]);
        } else {
            Log::warning('Falha no login tenant', [
                'email' => $credentials['email'] ?? 'não informado',
                'tenant_id' => $currentTenant?->id,
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
        $currentTenant = $this->getCurrentTenant();
        
        if ($this->check()) {
            Log::info('Logout tenant', [
                'user_id' => $this->id(),
                'email' => $this->user()->email,
                'tenant_id' => $currentTenant?->id,
            ]);
        }

        // Limpar sessão tenant
        $this->session->forget('tenant_user');
        $this->session->forget('current_context');
        
        parent::logout();
    }

    /**
     * Configurar scope de tenant
     */
    protected function configureTenantScope(): void
    {
        $currentTenant = $this->getCurrentTenant();
        
        if (!$currentTenant) {
            return;
        }

        // Habilitar tenant scopes
        $this->tenantManager->enable();
        $this->tenantManager->addTenant('tenant_id', $currentTenant->id);
    }

    /**
     * Configurar sessão específica do tenant
     */
    protected function configureTenantSession(): void
    {
        if (!$this->check()) {
            return;
        }

        $user = $this->user();
        $currentTenant = $this->getCurrentTenant();
        
        // Configurar contexto tenant
        $this->session->put('current_context', 'tenant');
        $this->session->put('tenant_user', [
            'id' => $user->getAuthIdentifier(),
            'name' => $user->name,
            'email' => $user->email,
            'tenant_id' => $currentTenant?->id,
            'is_tenant' => true,
            'login_at' => now()->toISOString(),
        ]);

        if ($currentTenant) {
            $this->session->put('current_tenant', [
                'id' => $currentTenant->id,
                'slug' => $currentTenant->slug,
                'name' => $currentTenant->name,
                'domain' => $currentTenant->domain,
            ]);
            $this->session->put('current_tenant_id', $currentTenant->id);
        }

        // Limpar qualquer contexto de landlord anterior
        $this->session->forget('landlord_user');
    }

    /**
     * Obter tenant atual
     *
     * @return Tenant|null
     */
    public function getCurrentTenant(): ?Tenant
    {
        if ($this->currentTenant) {
            return $this->currentTenant;
        }

        // Tentar obter da sessão primeiro
        $tenantData = $this->session->get('current_tenant');
        if ($tenantData && isset($tenantData['id'])) {
            $this->currentTenant = Tenant::find($tenantData['id']);
            return $this->currentTenant;
        }

        // Tentar resolver via TenantManager
        $tenantId = $this->tenantManager->getTenantId('tenant_id');
        if ($tenantId) {
            $this->currentTenant = Tenant::find($tenantId);
            return $this->currentTenant;
        }

        return null;
    }

    /**
     * Definir tenant atual
     *
     * @param  Tenant  $tenant
     * @return void
     */
    public function setCurrentTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
        
        // Configurar scope
        $this->tenantManager->enable();
        $this->tenantManager->addTenant('tenant_id', $tenant->id);
        
        // Atualizar sessão
        $this->session->put('current_tenant', [
            'id' => $tenant->id,
            'slug' => $tenant->slug,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
        ]);
        $this->session->put('current_tenant_id', $tenant->id);
    }

    /**
     * Verificar se o usuário pode acessar o tenant atual
     *
     * @return bool
     */
    public function canAccessCurrentTenant(): bool
    {
        if (!$this->check()) {
            return false;
        }

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            return false;
        }

        $user = $this->user();
        
        // Verificar se o usuário pertence ao tenant
        if (method_exists($user, 'tenants')) {
            return $user->tenants()->where('tenant_id', $currentTenant->id)->exists();
        }

        // Verificar através de relacionamento direto
        if (isset($user->tenant_id)) {
            return $user->tenant_id === $currentTenant->id;
        }

        return true; // Por padrão, permitir acesso
    }

    /**
     * Alternar para outro tenant (se o usuário tiver acesso)
     *
     * @param  int  $tenantId
     * @return bool
     */
    public function switchToTenant(int $tenantId): bool
    {
        if (!$this->check()) {
            return false;
        }

        $user = $this->user();
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant || $tenant->status !== 'active') {
            return false;
        }

        // Verificar se o usuário tem acesso ao tenant
        if (method_exists($user, 'tenants')) {
            if (!$user->tenants()->where('tenant_id', $tenantId)->exists()) {
                return false;
            }
        }

        $oldTenant = $this->getCurrentTenant();
        
        // Configurar novo tenant
        $this->setCurrentTenant($tenant);
        
        Log::info('Usuário alternou para outro tenant', [
            'user_id' => $this->id(),
            'old_tenant_id' => $oldTenant?->id,
            'new_tenant_id' => $tenant->id,
        ]);

        return true;
    }

    /**
     * Obter lista de tenants acessíveis pelo usuário
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAccessibleTenants()
    {
        if (!$this->check()) {
            return collect();
        }

        $user = $this->user();
        
        // Se o usuário tem relacionamento com tenants
        if (method_exists($user, 'tenants')) {
            return $user->tenants()
                ->where('status', 'active')
                ->get();
        }

        // Se o usuário pertence a um tenant específico
        if (isset($user->tenant_id)) {
            return Tenant::where('id', $user->tenant_id)
                ->where('status', 'active')
                ->get();
        }

        return collect();
    }

    /**
     * Obter estatísticas do tenant atual
     *
     * @return array
     */
    public function getTenantStats(): array
    {
        if (!$this->check()) {
            return [];
        }

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            return [];
        }

        $cacheKey = "tenant_stats_{$currentTenant->id}_{$this->id()}";
        
        return Cache::remember($cacheKey, 300, function () use ($currentTenant) { // 5 minutos
            $stats = [
                'tenant_id' => $currentTenant->id,
                'tenant_name' => $currentTenant->name,
                'tenant_slug' => $currentTenant->slug,
                'tenant_status' => $currentTenant->status,
                'user_id' => $this->id(),
                'accessible_tenants_count' => $this->getAccessibleTenants()->count(),
            ];

            // Adicionar estatísticas específicas do tenant se existir método
            if (method_exists($currentTenant, 'getStats')) {
                $stats['tenant_specific_stats'] = $currentTenant->getStats();
            }

            return $stats;
        });
    }

    /**
     * Verificar se o usuário é admin do tenant atual
     *
     * @return bool
     */
    public function isTenantAdmin(): bool
    {
        if (!$this->check()) {
            return false;
        }

        $user = $this->user();
        $currentTenant = $this->getCurrentTenant();
        
        if (!$currentTenant) {
            return false;
        }

        // Verificar através de roles
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || 
                   $user->hasRole("tenant-{$currentTenant->id}-admin");
        }

        // Verificar através de permissões
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission('tenant.admin') ||
                   $user->hasPermission("tenant.{$currentTenant->id}.admin");
        }

        return false;
    }

    /**
     * Verificar se o tenant atual está ativo
     *
     * @return bool
     */
    public function isTenantActive(): bool
    {
        $currentTenant = $this->getCurrentTenant();
        return $currentTenant && $currentTenant->status === 'active';
    }

    /**
     * Obter configurações específicas do tenant
     *
     * @return array
     */
    public function getTenantSettings(): array
    {
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            return [];
        }

        $cacheKey = "tenant_settings_{$currentTenant->id}";
        
        return Cache::remember($cacheKey, 600, function () use ($currentTenant) { // 10 minutos
            $settings = [
                'tenant_id' => $currentTenant->id,
                'name' => $currentTenant->name,
                'slug' => $currentTenant->slug,
                'domain' => $currentTenant->domain,
                'status' => $currentTenant->status,
            ];

            // Adicionar configurações específicas se existir
            if (method_exists($currentTenant, 'getSettings')) {
                $settings['custom_settings'] = $currentTenant->getSettings();
            }

            return $settings;
        });
    }

    /**
     * Limpar cache do tenant
     *
     * @return void
     */
    public function clearTenantCache(): void
    {
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            return;
        }

        $patterns = [
            "tenant_stats_{$currentTenant->id}_*",
            "tenant_settings_{$currentTenant->id}",
            "tenant_users_{$currentTenant->id}_*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        Log::info('Cache do tenant limpo', [
            'tenant_id' => $currentTenant->id,
            'user_id' => $this->id(),
        ]);
    }

    /**
     * Verificar se o usuário pode executar uma ação específica no tenant
     *
     * @param  string  $action
     * @return bool
     */
    public function canPerformAction(string $action): bool
    {
        if (!$this->check()) {
            return false;
        }

        $user = $this->user();
        $currentTenant = $this->getCurrentTenant();
        
        if (!$currentTenant) {
            return false;
        }

        // Verificar permissões específicas
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($action) ||
                   $user->hasPermission("tenant.{$currentTenant->id}.{$action}");
        }

        return true; // Por padrão, permitir
    }
} 