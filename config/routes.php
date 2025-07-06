<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lara Gatekeeper Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para as rotas do sistema dual guard
    |
    */

    'landlord' => [
        'enabled' => true,
        'prefix' => 'landlord',
        'name' => 'landlord.',
        'middleware' => ['web', 'landlord', 'auth:landlord'],
        'domain' => null, // ou 'admin.{domain}' para subdomínio
        'controllers' => [
            'dashboard' => \Callcocam\LaraGatekeeper\Http\Controllers\Landlord\DashboardController::class,
            'tenants' => \Callcocam\LaraGatekeeper\Http\Controllers\Landlord\TenantController::class,
            'users' => \Callcocam\LaraGatekeeper\Http\Controllers\Landlord\UserController::class,
            'roles' => \Callcocam\LaraGatekeeper\Http\Controllers\Landlord\RoleController::class,
            'permissions' => \Callcocam\LaraGatekeeper\Http\Controllers\Landlord\PermissionController::class,
        ],
    ],

    'tenant' => [
        'enabled' => true,
        'prefix' => 'tenant',
        'name' => 'tenant.',
        'middleware' => ['web', 'tenant', 'auth:tenant'],
        'domain' => null, // ou '{tenant}.{domain}' para subdomínio
        'controllers' => [
            'dashboard' => \Callcocam\LaraGatekeeper\Http\Controllers\Tenant\DashboardController::class,
            'users' => \Callcocam\LaraGatekeeper\Http\Controllers\Tenant\UserController::class,
            'roles' => \Callcocam\LaraGatekeeper\Http\Controllers\Tenant\RoleController::class,
            'permissions' => \Callcocam\LaraGatekeeper\Http\Controllers\Tenant\PermissionController::class,
            'settings' => \Callcocam\LaraGatekeeper\Http\Controllers\Tenant\TenantController::class,
        ],
    ],

    'compatibility' => [
        'enabled' => true,
        'redirect_to_context' => true,
        'legacy_routes' => [
            'admin' => 'landlord',
            'dashboard' => 'auto', // auto-detecta contexto
        ],
    ],

    'features' => [
        'impersonation' => true,
        'tenant_switching' => true,
        'bulk_actions' => true,
        'export_import' => true,
        'advanced_search' => true,
        'reports' => true,
    ],

    'url_patterns' => [
        'landlord' => [
            'admin.*',
            '*/landlord/*',
            'landlord.*',
        ],
        'tenant' => [
            '{tenant}.*',
            '*/tenant/*',
            'tenant.*',
        ],
    ],

    'route_caching' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hora
    ],
]; 