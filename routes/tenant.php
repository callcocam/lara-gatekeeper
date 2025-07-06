<?php

use Illuminate\Support\Facades\Route;
use Callcocam\LaraGatekeeper\Http\Controllers\Tenant\RoleController;
use Callcocam\LaraGatekeeper\Http\Controllers\Tenant\PermissionController;
use Callcocam\LaraGatekeeper\Http\Controllers\Tenant\UserController;
use Callcocam\LaraGatekeeper\Http\Controllers\Tenant\TenantController;
use Callcocam\LaraGatekeeper\Http\Controllers\Tenant\DashboardController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Rotas específicas para o contexto Tenant - Gerenciamento por tenant
| Estas rotas são acessadas via tenant.dominio.com ou dominio.com/tenant
|
*/

Route::prefix('tenant')->name('tenant.')->middleware([
    'web',
    'tenant',
    'auth:tenant',
])->group(function () {

    // Dashboard Tenant
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Configurações do Tenant Atual
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [TenantController::class, 'settings'])->name('index');
        Route::post('update', [TenantController::class, 'updateSettings'])->name('update');
        Route::post('logo', [TenantController::class, 'uploadLogo'])->name('upload-logo');
        Route::delete('logo', [TenantController::class, 'removeLogo'])->name('remove-logo');
    });

    // Gerenciamento de Usuários do Tenant
    Route::resource('users', UserController::class);
    Route::prefix('users')->name('users.')->group(function () {
        Route::post('{user}/roles', [UserController::class, 'syncRoles'])->name('sync-roles');
        Route::post('{user}/avatar', [UserController::class, 'uploadAvatar'])->name('upload-avatar');
        Route::delete('{user}/avatar', [UserController::class, 'removeAvatar'])->name('remove-avatar');
        Route::post('{user}/activate', [UserController::class, 'activate'])->name('activate');
        Route::post('{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
    });

    // Gerenciamento de Roles do Tenant
    Route::resource('roles', RoleController::class);
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::post('{role}/permissions', [RoleController::class, 'syncPermissions'])->name('sync-permissions');
        Route::post('{role}/duplicate', [RoleController::class, 'duplicate'])->name('duplicate');
    });

    // Gerenciamento de Permissões do Tenant
    Route::resource('permissions', PermissionController::class);
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::post('generate-resource', [PermissionController::class, 'generateResourcePermissions'])->name('generate-resource');
        Route::post('{permission}/duplicate', [PermissionController::class, 'duplicate'])->name('duplicate');
    });

    // Rotas para busca e filtros avançados
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('users', [UserController::class, 'search'])->name('users');
        Route::get('roles', [RoleController::class, 'search'])->name('roles');
        Route::get('permissions', [PermissionController::class, 'search'])->name('permissions');
    });

    // Rotas para exportação
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('users', [UserController::class, 'export'])->name('users');
        Route::get('roles', [RoleController::class, 'export'])->name('roles');
        Route::get('permissions', [PermissionController::class, 'export'])->name('permissions');
    });

    // Rotas para importação
    Route::prefix('import')->name('import.')->group(function () {
        Route::post('users', [UserController::class, 'import'])->name('users');
        Route::post('roles', [RoleController::class, 'import'])->name('roles');
        Route::post('permissions', [PermissionController::class, 'import'])->name('permissions');
    });

    // Rotas para ações em lote
    Route::prefix('bulk')->name('bulk.')->group(function () {
        Route::post('users/delete', [UserController::class, 'bulkDelete'])->name('users.delete');
        Route::post('users/status', [UserController::class, 'bulkUpdateStatus'])->name('users.status');
        
        Route::post('roles/delete', [RoleController::class, 'bulkDelete'])->name('roles.delete');
        Route::post('roles/status', [RoleController::class, 'bulkUpdateStatus'])->name('roles.status');
        
        Route::post('permissions/delete', [PermissionController::class, 'bulkDelete'])->name('permissions.delete');
        Route::post('permissions/status', [PermissionController::class, 'bulkUpdateStatus'])->name('permissions.status');
    });

    // Rotas para relatórios do tenant
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [DashboardController::class, 'reports'])->name('index');
        Route::get('users', [UserController::class, 'reports'])->name('users');
        Route::get('activity', [DashboardController::class, 'activityReport'])->name('activity');
    });

    // Rotas para perfil do usuário
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [UserController::class, 'profile'])->name('index');
        Route::post('update', [UserController::class, 'updateProfile'])->name('update');
        Route::post('password', [UserController::class, 'updatePassword'])->name('password');
        Route::post('avatar', [UserController::class, 'updateAvatar'])->name('avatar');
    });
});

// Rotas para subdomínios de tenant
Route::domain('{tenant}.{domain}')->middleware([
    'web',
    'tenant',
    'auth:tenant',
])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('tenant.dashboard.subdomain');
    
    // Redirecionar para as rotas principais do tenant
    Route::any('{path}', function ($tenant, $domain, $path) {
        return redirect()->route('tenant.dashboard');
    })->where('path', '.*');
});

// Rotas públicas do tenant (sem autenticação)
Route::prefix('tenant')->name('tenant.public.')->middleware([
    'web',
    'tenant-resolver',
])->group(function () {
    Route::get('info', [TenantController::class, 'publicInfo'])->name('info');
    Route::get('status', [TenantController::class, 'status'])->name('status');
}); 