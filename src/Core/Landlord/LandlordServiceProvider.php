<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Landlord;

use Callcocam\LaraGatekeeper\Models\Tenant;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class LandlordServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootTenant();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TenantManager::class, function () {
            return new TenantManager();
        });
    }

    public function bootTenant()
    {

        if (app()->runningInConsole()) {
            return false;
        }
        $tenant = null;
        try {
            // Verifica se é um subdominio
            $host = request()->getHost();
            $tenant = app($this->getModel())->query()->where('domain',   $host)->first();
            if (!$tenant) :
                $subdomain = explode('.', $host);
                $host = $subdomain[0];
                $tenant = app($this->getModel())->query()->where('prefix',   $host)->first();
                if (!$tenant) :
                    die(response("Nenhuma empresa cadastrada com esse endereço " .  $host, 401));
                endif;
            endif;
            if ($tenant) {
                app(TenantManager::class)->addTenant("tenant_id", data_get($tenant, 'id'));
                // config(['app.url'=> sprintf("%s://%s", request()->getScheme(), $tenant->domain)]);
                config([
                    'app.tenant_id' => $tenant->id,
                    'app.name' => Str::limit($tenant->name, 20, '...'),
                    'app.cover_photo_url' => $tenant->cover_photo_url,
                    'app.tenant' => $tenant->toArray(),
                    'app.tenant.company_id' => $tenant->old_id,
                ]);
            }
        } catch (\PDOException $th) {

            throw $th;
        }
    }

    public  function getModel(): string
    {
        return config('tenant.models.tenant',  Tenant::class);
    }
}
