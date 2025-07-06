<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Callcocam\LaraGatekeeper\Models\Tenant;
use Callcocam\LaraGatekeeper\Core\Landlord\TenantManager;

class TenantResolver
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
        // Verificar se a resolução de URL está habilitada
        if (!config('lara-gatekeeper.url_resolution.enabled', true)) {
            return $next($request);
        }

        $tenantSlug = $this->resolveTenantFromUrl($request);
        
        if ($tenantSlug) {
            $tenant = $this->findTenant($tenantSlug);
            
            if ($tenant) {
                // Configurar o tenant no TenantManager
                $this->tenantManager->addTenant('tenant_id', $tenant->id);
                
                // Armazenar informações do tenant na sessão
                Session::put('current_tenant', [
                    'id' => $tenant->id,
                    'slug' => $tenant->slug,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ]);

                // Configurar o tenant no request para uso posterior
                $request->attributes->set('tenant', $tenant);
                $request->attributes->set('tenant_id', $tenant->id);
                $request->attributes->set('is_tenant_context', true);
            }
        }

        return $next($request);
    }

    /**
     * Resolve tenant slug from URL
     */
    protected function resolveTenantFromUrl(Request $request): ?string
    {
        $host = $request->getHost();
        $path = $request->path();
        
        // Verificar se é detecção por subdomínio
        if (config('lara-gatekeeper.url_resolution.subdomain_detection', true)) {
            $tenantSlug = $this->resolveFromSubdomain($host);
            if ($tenantSlug) {
                return $tenantSlug;
            }
        }

        // Verificar se é detecção por path
        if (config('lara-gatekeeper.url_resolution.path_detection', true)) {
            $tenantSlug = $this->resolveFromPath($path);
            if ($tenantSlug) {
                return $tenantSlug;
            }
        }

        // Verificar parâmetro na URL
        $tenantParameter = config('lara-gatekeeper.url_resolution.tenant_parameter', 'tenant_slug');
        if ($request->has($tenantParameter)) {
            return $request->get($tenantParameter);
        }

        return null;
    }

    /**
     * Resolve tenant from subdomain
     */
    protected function resolveFromSubdomain(string $host): ?string
    {
        $landlordDomains = config('lara-gatekeeper.url_resolution.landlord_domains', ['admin', 'landlord']);
        $tenantDomains = config('lara-gatekeeper.url_resolution.tenant_domains', ['app', 'tenant']);
        
        // Extrair subdomínio
        $parts = explode('.', $host);
        
        if (count($parts) >= 2) {
            $subdomain = $parts[0];
            
            // Verificar se não é um domínio de landlord
            if (in_array($subdomain, $landlordDomains)) {
                return null;
            }
            
            // Verificar se é um domínio de tenant conhecido ou qualquer subdomínio
            if (in_array($subdomain, $tenantDomains) || !in_array($subdomain, ['www', 'api'])) {
                return $subdomain;
            }
        }

        return null;
    }

    /**
     * Resolve tenant from path
     */
    protected function resolveFromPath(string $path): ?string
    {
        // Verificar se o path começa com /tenant/{slug}
        if (preg_match('/^tenant\/([^\/]+)/', $path, $matches)) {
            return $matches[1];
        }

        // Verificar se o path começa com /{slug} (assumindo que é um tenant)
        if (preg_match('/^([^\/]+)/', $path, $matches)) {
            $slug = $matches[1];
            
            // Verificar se não é uma rota conhecida do sistema
            $systemRoutes = ['api', 'admin', 'landlord', 'auth', 'login', 'register', 'dashboard'];
            if (!in_array($slug, $systemRoutes)) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Find tenant by slug
     */
    protected function findTenant(string $slug): ?Tenant
    {
        $cacheKey = "tenant_by_slug_{$slug}";
        $cacheTtl = config('lara-gatekeeper.cache.ttl', 3600);

        if (config('lara-gatekeeper.cache.enabled', true)) {
            return Cache::remember($cacheKey, $cacheTtl, function () use ($slug) {
                return Tenant::where('slug', $slug)
                    ->where('status', 'active')
                    ->first();
            });
        }

        return Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get current tenant from session
     */
    public static function getCurrentTenant(): ?array
    {
        return Session::get('current_tenant');
    }

    /**
     * Check if current context is tenant
     */
    public static function isTenantContext(): bool
    {
        return !is_null(self::getCurrentTenant());
    }

    /**
     * Get current tenant ID
     */
    public static function getCurrentTenantId(): ?int
    {
        $tenant = self::getCurrentTenant();
        return $tenant ? $tenant['id'] : null;
    }
} 