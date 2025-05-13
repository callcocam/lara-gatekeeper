<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Core\Shinobi;

use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Access\Authorizable;

class ShinobiServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return null
     */
    public function boot()
    { 
        $this->mergeConfigFrom(__DIR__.'/../../../config/shinobi.php', 'shinobi');

        $this->registerGates();
        $this->registerBladeDirectives();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        
        $this->app->singleton('shinobi', function ($app) {
            $auth = $app->make('Illuminate\Contracts\Auth\Guard');

            return new \Callcocam\LaraGatekeeper\Core\Shinobi\Shinobi($auth);
        });
    }

    /**
     * Register the permission gates.
     * 
     * @return void
     */
    protected function registerGates()
    {
        Gate::before(function(Authorizable $user, String $permission) {
            try {
                if (method_exists($user, 'hasPermissionTo')) {
                    return $user->hasPermissionTo($permission) ?: null;
                }
            } catch (Exception $e) {
                // 
                // dd($e);
            }
        });
    }

    /**
     * Register the blade directives.
     *
     * @return void
     */
    protected function registerBladeDirectives()
    {
        Blade::if('role', function($role) {
            return auth()->user() and auth()->user()->hasRole($role);
        });

        Blade::if('anyrole', function(...$roles) {
            return auth()->user() and auth()->user()->hasAnyRole(...$roles);
        });

        Blade::if('allroles', function(...$roles) {
            return auth()->user() and auth()->user()->hasAllRoles(...$roles);
        });
    }
 
}
