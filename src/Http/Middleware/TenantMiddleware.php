<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Callcocam\LaraGatekeeper\Core\Landlord\TenantManager;

class TenantMiddleware
{
    protected TenantManager $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar se estamos em contexto de tenant
        if (!$this->isTenantContext($request)) {
            return $this->redirectToTenantSelection($request);
        }

        // Configurar o guard padrão para tenant
        Config::set('auth.defaults.guard', 'tenant');

        // Verificar se o usuário está autenticado no guard tenant
        if (!Auth::guard('tenant')->check()) {
            return $this->redirectToTenantLogin($request);
        }

        // Verificar se o usuário tem acesso ao tenant atual
        if (!$this->userHasAccessToTenant($request)) {
            return $this->redirectToAccessDenied($request);
        }

        // Configurar scopes do tenant para o usuário atual
        $this->configureTenantScopes($request);

        return $next($request);
    }

    /**
     * Verificar se estamos em contexto de tenant
     */
    protected function isTenantContext(Request $request): bool
    {
        return $request->attributes->has('tenant') || TenantResolver::isTenantContext();
    }

    /**
     * Verificar se o usuário tem acesso ao tenant atual
     */
    protected function userHasAccessToTenant(Request $request): bool
    {
        $user = Auth::guard('tenant')->user();
        $tenantId = $request->attributes->get('tenant_id') ?? TenantResolver::getCurrentTenantId();

        if (!$user || !$tenantId) {
            return false;
        }

        // Verificar se o usuário pertence ao tenant atual
        // Assumindo que existe uma relação entre user e tenant
        if (method_exists($user, 'tenants')) {
            return $user->tenants()->where('tenant_id', $tenantId)->exists();
        }

        // Verificar se o usuário tem tenant_id correspondente
        if (isset($user->tenant_id)) {
            return $user->tenant_id == $tenantId;
        }

        // Por padrão, permitir acesso (pode ser ajustado conforme necessário)
        return true;
    }

    /**
     * Configurar scopes do tenant
     */
    protected function configureTenantScopes(Request $request): void
    {
        $tenantId = $request->attributes->get('tenant_id') ?? TenantResolver::getCurrentTenantId();
        
        if ($tenantId) {
            // Garantir que o tenant está configurado no TenantManager
            if (!$this->tenantManager->hasTenant('tenant_id')) {
                $this->tenantManager->addTenant('tenant_id', $tenantId);
            }

            // Aplicar scopes aos modelos deferred
            $this->tenantManager->applyTenantScopesToDeferredModels();
        }
    }

    /**
     * Redirecionar para seleção de tenant
     */
    protected function redirectToTenantSelection(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tenant não identificado.',
                'error' => 'tenant_not_found'
            ], 404);
        }

        return redirect()->route('tenant.select')
            ->with('error', 'Tenant não identificado. Selecione um tenant válido.');
    }

    /**
     * Redirecionar para login do tenant
     */
    protected function redirectToTenantLogin(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Não autenticado.',
                'error' => 'unauthenticated'
            ], 401);
        }

        $tenantSlug = TenantResolver::getCurrentTenant()['slug'] ?? 'tenant';
        
        return redirect()->route('tenant.login', ['tenant' => $tenantSlug])
            ->with('info', 'Faça login para acessar este tenant.');
    }

    /**
     * Redirecionar para acesso negado
     */
    protected function redirectToAccessDenied(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Acesso negado a este tenant.',
                'error' => 'access_denied'
            ], 403);
        }

        return redirect()->route('tenant.access-denied')
            ->with('error', 'Você não tem permissão para acessar este tenant.');
    }

    /**
     * Obter informações do tenant atual
     */
    public static function getCurrentTenantInfo(): ?array
    {
        return TenantResolver::getCurrentTenant();
    }

    /**
     * Verificar se o usuário é super admin (bypass tenant scopes)
     */
    protected function isSuperAdmin(): bool
    {
        $user = Auth::guard('tenant')->user();
        
        if (!$user) {
            return false;
        }

        // Verificar se o usuário tem uma role específica de super admin
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('super-admin') || $user->hasRole('landlord');
        }

        return false;
    }
} 