<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Landlord\Guards\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Callcocam\LaraGatekeeper\Core\Landlord\Guards\LandlordGuard landlord()
 * @method static \Callcocam\LaraGatekeeper\Core\Landlord\Guards\TenantGuard tenant()
 * @method static bool isLandlordContext()
 * @method static bool isTenantContext()
 * @method static string|null getCurrentContext()
 * @method static \Callcocam\LaraGatekeeper\Core\Landlord\Guards\LandlordGuard|\Callcocam\LaraGatekeeper\Core\Landlord\Guards\TenantGuard|null getActiveGuard()
 * @method static bool isAuthenticated()
 * @method static \Illuminate\Contracts\Auth\Authenticatable|null getAuthenticatedUser()
 * @method static void logoutAll()
 * @method static bool switchToLandlord()
 * @method static bool switchToTenant(int $tenantId)
 * @method static bool impersonateTenant(int $tenantId)
 * @method static bool stopImpersonation()
 * @method static bool isImpersonating()
 * @method static array|null getImpersonationData()
 * @method static \Callcocam\LaraGatekeeper\Models\Tenant|null getCurrentTenant()
 * @method static array getState()
 * @method static bool can(string $action, $resource = null)
 * @method static void clearAllCaches()
 *
 * @see \Callcocam\LaraGatekeeper\Core\Landlord\Guards\GuardManager
 */
class GuardManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Callcocam\LaraGatekeeper\Core\Landlord\Guards\GuardManager::class;
    }
} 