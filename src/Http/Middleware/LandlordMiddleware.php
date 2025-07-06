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

class LandlordMiddleware
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
        // Verificar se estamos em contexto de landlord
        if (!$this->isLandlordContext($request)) {
            return $this->redirectToLandlordAccess($request);
        }

        // Configurar o guard padrão para landlord
        Config::set('auth.defaults.guard', 'landlord');

        // Verificar se o usuário está autenticado no guard landlord
        if (!Auth::guard('landlord')->check()) {
            return $this->redirectToLandlordLogin($request);
        }

        // Verificar se o usuário tem permissões de landlord
        if (!$this->userHasLandlordPermissions($request)) {
            return $this->redirectToAccessDenied($request);
        }

        // Configurar contexto landlord (bypass tenant scopes)
        $this->configureLandlordContext($request);

        return $next($request);
    }

    /**
     * Verificar se estamos em contexto de landlord
     */
    protected function isLandlordContext(Request $request): bool
    {
        $host = $request->getHost();
        $path = $request->path();
        
        $landlordDomains = config('lara-gatekeeper.url_resolution.landlord_domains', ['admin', 'landlord']);
        
        // Verificar por subdomínio
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            $subdomain = $parts[0];
            if (in_array($subdomain, $landlordDomains)) {
                return true;
            }
        }

        // Verificar por path
        if (str_starts_with($path, 'landlord/') || str_starts_with($path, 'admin/')) {
            return true;
        }

        // Verificar se foi explicitamente marcado como landlord
        return $request->attributes->get('is_landlord_context', false);
    }

    /**
     * Verificar se o usuário tem permissões de landlord
     */
    protected function userHasLandlordPermissions(Request $request): bool
    {
        $user = Auth::guard('landlord')->user();

        if (!$user) {
            return false;
        }

        // Verificar se o usuário tem role de landlord
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('landlord') || 
                   $user->hasRole('super-admin') || 
                   $user->hasRole('admin');
        }

        // Verificar se o usuário tem uma flag específica de landlord
        if (isset($user->is_landlord)) {
            return $user->is_landlord;
        }

        // Verificar se o usuário tem permissão específica de landlord
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission('landlord.access') || 
                   $user->hasPermission('landlord.*');
        }

        // Por padrão, negar acesso (segurança)
        return false;
    }

    /**
     * Configurar contexto landlord
     */
    protected function configureLandlordContext(Request $request): void
    {
        // Desabilitar tenant scopes para landlord
        $this->tenantManager->disable();

        // Marcar contexto como landlord
        $request->attributes->set('is_landlord_context', true);
        $request->attributes->set('bypass_tenant_scopes', true);

        // Armazenar informações do landlord na sessão
        Session::put('current_context', 'landlord');
        Session::put('landlord_user', [
            'id' => Auth::guard('landlord')->id(),
            'name' => Auth::guard('landlord')->user()->name,
            'email' => Auth::guard('landlord')->user()->email,
        ]);

        // Configurar bypass de tenant scopes globalmente
        Config::set('lara-gatekeeper.landlord.bypass_tenant_scopes', true);
    }

    /**
     * Redirecionar para acesso landlord
     */
    protected function redirectToLandlordAccess(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Acesso landlord requerido.',
                'error' => 'landlord_access_required'
            ], 403);
        }

        return redirect()->route('landlord.access')
            ->with('error', 'Acesso de landlord necessário para esta área.');
    }

    /**
     * Redirecionar para login do landlord
     */
    protected function redirectToLandlordLogin(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Autenticação landlord requerida.',
                'error' => 'landlord_authentication_required'
            ], 401);
        }

        return redirect()->route('landlord.login')
            ->with('info', 'Faça login como landlord para acessar esta área.');
    }

    /**
     * Redirecionar para acesso negado
     */
    protected function redirectToAccessDenied(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Permissões de landlord insuficientes.',
                'error' => 'insufficient_landlord_permissions'
            ], 403);
        }

        return redirect()->route('landlord.access-denied')
            ->with('error', 'Você não tem permissões de landlord suficientes.');
    }

    /**
     * Verificar se o contexto atual é landlord (método estático)
     */
    public static function isCurrentContextLandlord(): bool
    {
        return Session::get('current_context') === 'landlord';
    }

    /**
     * Obter informações do landlord atual
     */
    public static function getCurrentLandlordInfo(): ?array
    {
        return Session::get('landlord_user');
    }

    /**
     * Verificar se o usuário pode acessar tenant específico
     */
    protected function canAccessTenant(int $tenantId): bool
    {
        $user = Auth::guard('landlord')->user();
        
        if (!$user) {
            return false;
        }

        // Landlord pode acessar todos os tenants por padrão
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('super-admin') || $user->hasRole('landlord')) {
                return true;
            }
        }

        // Verificar se tem permissão específica para o tenant
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission("tenant.{$tenantId}.access") || 
                   $user->hasPermission('tenant.*.access');
        }

        return true; // Por padrão, landlord pode acessar qualquer tenant
    }

    /**
     * Alternar para contexto de tenant específico (impersonation)
     */
    public function switchToTenant(Request $request, int $tenantId)
    {
        if (!$this->canAccessTenant($tenantId)) {
            return $this->redirectToAccessDenied($request);
        }

        // Configurar tenant temporariamente
        $this->tenantManager->enable();
        $this->tenantManager->addTenant('tenant_id', $tenantId);

        // Marcar como impersonation
        Session::put('landlord_impersonating_tenant', $tenantId);
        Session::put('original_context', 'landlord');

        return redirect()->route('tenant.dashboard', ['tenant' => $tenantId])
            ->with('info', 'Você está visualizando como landlord o tenant ID: ' . $tenantId);
    }

    /**
     * Voltar para contexto landlord
     */
    public function returnToLandlord(Request $request)
    {
        Session::forget('landlord_impersonating_tenant');
        Session::forget('original_context');
        
        $this->tenantManager->disable();

        return redirect()->route('landlord.dashboard')
            ->with('info', 'Você retornou ao contexto landlord.');
    }
} 