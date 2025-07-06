<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
// config for Callcocam/LaraGatekeeper
return [
    
    /*
    |--------------------------------------------------------------------------
    | Guards de Autenticação
    |--------------------------------------------------------------------------
    |
    | Configuração dos guards para Landlord e Tenant
    | 
    */
    'guards' => [
        'landlord' => [
            'driver' => 'session',
            'provider' => 'landlord_users',
            'url_patterns' => [
                'admin.*',
                '*/landlord/*',
                '*/admin/*'
            ],
            'middleware' => ['web', 'landlord'],
            'redirect_route' => 'landlord.login',
        ],
        'tenant' => [
            'driver' => 'session', 
            'provider' => 'tenant_users',
            'url_patterns' => [
                '*',
                '*/tenant/*'
            ],
            'middleware' => ['web', 'tenant'],
            'redirect_route' => 'tenant.login',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers de Autenticação
    |--------------------------------------------------------------------------
    |
    | Configuração dos providers para cada guard
    |
    */
    'providers' => [
        'landlord_users' => [
            'driver' => 'eloquent',
            'model' => \Callcocam\LaraGatekeeper\Models\Auth\User::class,
            'table' => 'users',
            'scope' => 'landlord', // Escopo específico para landlord
        ],
        'tenant_users' => [
            'driver' => 'eloquent',
            'model' => \Callcocam\LaraGatekeeper\Models\Auth\User::class,
            'table' => 'users',
            'scope' => 'tenant', // Escopo específico para tenant
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resolução de Contexto por URL
    |--------------------------------------------------------------------------
    |
    | Configurações para identificar automaticamente o contexto baseado na URL
    |
    */
    'url_resolution' => [
        'enabled' => true,
        'tenant_parameter' => 'tenant_slug',
        'landlord_domains' => ['admin', 'landlord'],
        'tenant_domains' => ['app', 'tenant'],
        'default_guard' => 'tenant',
        'subdomain_detection' => true,
        'path_detection' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações do Landlord (Existente)
    |--------------------------------------------------------------------------
    |
    | Mantém compatibilidade com a estrutura atual do Core/Landlord
    |
    */
    'landlord' => [
        'default_tenant_columns' => ['tenant_id'],
        'bypass_tenant_scopes' => true, // Landlord ignora scopes de tenant
        'cache_tenants' => true,
        'cache_duration' => 3600, // 1 hora
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Tenant
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para o contexto de tenant
    |
    */
    'tenant' => [
        'enforce_tenant_scopes' => true,
        'auto_scope_models' => true,
        'tenant_columns' => ['tenant_id'],
        'cache_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rotas e Middlewares
    |--------------------------------------------------------------------------
    |
    | Configuração de rotas e middlewares para cada contexto
    |
    */
    'routes' => [
        'landlord' => [
            'prefix' => 'landlord',
            'middleware' => ['web', 'landlord'],
            'namespace' => 'Callcocam\LaraGatekeeper\Http\Controllers\Landlord',
            'as' => 'landlord.',
        ],
        'tenant' => [
            'prefix' => 'tenant',
            'middleware' => ['web', 'tenant'],
            'namespace' => 'Callcocam\LaraGatekeeper\Http\Controllers\Tenant',
            'as' => 'tenant.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Segurança
    |--------------------------------------------------------------------------
    |
    | Configurações de segurança e controle de acesso
    |
    */
    'security' => [
        'enable_csrf' => true,
        'session_timeout' => 7200, // 2 horas
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Cache
    |--------------------------------------------------------------------------
    |
    | Configurações de cache para melhor performance
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hora
        'prefix' => 'lara_gatekeeper',
        'tags' => ['permissions', 'roles', 'tenants'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Logging
    |--------------------------------------------------------------------------
    |
    | Configurações de logging para auditoria
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'daily',
        'level' => 'info',
        'log_tenant_changes' => true,
        'log_guard_switches' => true,
    ],

];
