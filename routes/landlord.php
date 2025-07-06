<?php

use Illuminate\Support\Facades\Route;
use Callcocam\LaraGatekeeper\Http\Controllers\Landlord\RoleController;
use Callcocam\LaraGatekeeper\Http\Controllers\Landlord\PermissionController;
use Callcocam\LaraGatekeeper\Http\Controllers\Landlord\UserController;
use Callcocam\LaraGatekeeper\Http\Controllers\Landlord\TenantController;
use Callcocam\LaraGatekeeper\Http\Controllers\Landlord\DashboardController;

/*
|--------------------------------------------------------------------------
| Landlord Routes
|--------------------------------------------------------------------------
|
| Rotas específicas para o contexto Landlord - Gerenciamento global
| Estas rotas são acessadas via admin.dominio.com ou dominio.com/landlord
|
*/

Route::prefix('landlord')->name('landlord.')->middleware([
    'web',
    'landlord',
    'auth:landlord',
])->group(function () {

    // Dashboard Landlord
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/tenants-overview', [DashboardController::class, 'tenantsOverview'])->name('tenants.overview');

    // Gerenciamento Global de Tenants
    Route::resource('tenants', TenantController::class);
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::post('{tenant}/suspend', [TenantController::class, 'suspend'])->name('suspend');
        Route::post('{tenant}/activate', [TenantController::class, 'activate'])->name('activate');
        Route::post('{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('impersonate');
        Route::post('{tenant}/clone', [TenantController::class, 'clone'])->name('clone');
        Route::post('{tenant}/logo', [TenantController::class, 'uploadLogo'])->name('upload-logo');
        Route::delete('{tenant}/logo', [TenantController::class, 'removeLogo'])->name('remove-logo');
    });

    // Gerenciamento Global de Usuários
    Route::resource('users', UserController::class);
    Route::prefix('users')->name('users.')->group(function () {
        Route::post('{user}/impersonate', [UserController::class, 'impersonate'])->name('impersonate');
        Route::post('stop-impersonation', [UserController::class, 'stopImpersonation'])->name('stop-impersonation');
        Route::post('{user}/roles', [UserController::class, 'syncRoles'])->name('sync-roles');
        Route::post('{user}/avatar', [UserController::class, 'uploadAvatar'])->name('upload-avatar');
        Route::delete('{user}/avatar', [UserController::class, 'removeAvatar'])->name('remove-avatar');
    });

    // Gerenciamento Global de Roles
    Route::resource('roles', RoleController::class);
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::post('{role}/permissions', [RoleController::class, 'syncPermissions'])->name('sync-permissions');
        Route::post('{role}/sync-tenants', [RoleController::class, 'syncWithTenants'])->name('sync-tenants');
        Route::post('{role}/clone-tenant', [RoleController::class, 'cloneToTenant'])->name('clone-tenant');
    });

    // Gerenciamento Global de Permissões
    Route::resource('permissions', PermissionController::class);
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::post('generate-resource', [PermissionController::class, 'generateResourcePermissions'])->name('generate-resource');
        Route::post('{permission}/clone-tenant', [PermissionController::class, 'cloneToTenant'])->name('clone-tenant');
    });

    // Rotas para busca e filtros avançados
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('tenants', [TenantController::class, 'search'])->name('tenants');
        Route::get('users', [UserController::class, 'search'])->name('users');
        Route::get('roles', [RoleController::class, 'search'])->name('roles');
        Route::get('permissions', [PermissionController::class, 'search'])->name('permissions');
    });

    // Rotas para exportação
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('tenants', [TenantController::class, 'export'])->name('tenants');
        Route::get('users', [UserController::class, 'export'])->name('users');
        Route::get('roles', [RoleController::class, 'export'])->name('roles');
        Route::get('permissions', [PermissionController::class, 'export'])->name('permissions');
    });

    // Rotas para importação
    Route::prefix('import')->name('import.')->group(function () {
        Route::post('tenants', [TenantController::class, 'import'])->name('tenants');
        Route::post('users', [UserController::class, 'import'])->name('users');
        Route::post('roles', [RoleController::class, 'import'])->name('roles');
        Route::post('permissions', [PermissionController::class, 'import'])->name('permissions');
    });

    // Rotas para ações em lote
    Route::prefix('bulk')->name('bulk.')->group(function () {
        Route::post('tenants/delete', [TenantController::class, 'bulkDelete'])->name('tenants.delete');
        Route::post('tenants/status', [TenantController::class, 'bulkUpdateStatus'])->name('tenants.status');
        
        Route::post('users/delete', [UserController::class, 'bulkDelete'])->name('users.delete');
        Route::post('users/status', [UserController::class, 'bulkUpdateStatus'])->name('users.status');
        
        Route::post('roles/delete', [RoleController::class, 'bulkDelete'])->name('roles.delete');
        Route::post('roles/status', [RoleController::class, 'bulkUpdateStatus'])->name('roles.status');
        
        Route::post('permissions/delete', [PermissionController::class, 'bulkDelete'])->name('permissions.delete');
        Route::post('permissions/status', [PermissionController::class, 'bulkUpdateStatus'])->name('permissions.status');
    });

    // Rotas para relatórios e estatísticas
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [DashboardController::class, 'reports'])->name('index');
        Route::get('tenants', [TenantController::class, 'reports'])->name('tenants');
        Route::get('users', [UserController::class, 'reports'])->name('users');
        Route::get('system-health', [DashboardController::class, 'systemHealth'])->name('system-health');
    });

    // Rotas para configurações globais
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [DashboardController::class, 'settings'])->name('index');
        Route::post('update', [DashboardController::class, 'updateSettings'])->name('update');
    });
});

// Rotas alternativas para domínio admin
Route::domain('admin.{domain}')->middleware([
    'web',
    'landlord',
    'auth:landlord',
])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('landlord.dashboard.alt');
    
    // Redirecionar para as rotas principais do landlord
    Route::any('{path}', function ($domain, $path) {
        return redirect()->route('landlord.dashboard');
    })->where('path', '.*');
}); 