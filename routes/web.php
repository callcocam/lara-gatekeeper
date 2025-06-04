<?php

use Illuminate\Support\Facades\Route;
use Callcocam\LaraGatekeeper\Http\Controllers\RoleController;
use Callcocam\LaraGatekeeper\Http\Controllers\PermissionController;
use Callcocam\LaraGatekeeper\Http\Controllers\UserController;
use Callcocam\LaraGatekeeper\Http\Controllers\TenantController;
use Callcocam\LaraGatekeeper\Http\Controllers\Components\AddressController;
use Callcocam\LaraGatekeeper\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Lara Gatekeeper Web Routes
|--------------------------------------------------------------------------
|
| Rotas do pacote Lara Gatekeeper para funcionalidades CRUD
| Estas rotas são registradas automaticamente pelo service provider
|
*/

Route::prefix('admin')->name('admin.')  ->middleware([
    'web',
    'auth',
])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Rotas para Papéis (Roles)
    Route::resource('roles', RoleController::class);

    // Rotas para Permissões (Permissions)
    Route::resource('permissions', PermissionController::class);

    // Rotas para Usuários (Users)
    Route::resource('users', UserController::class);

    // Rotas para Tenants
    Route::resource('tenants', TenantController::class);

    // Rotas para Endereços (Addresses)
    Route::resource('addresses', AddressController::class);

    // Rotas adicionais para funcionalidades específicas
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::post('{role}/permissions', [RoleController::class, 'syncPermissions'])->name('sync-permissions');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::post('{user}/roles', [UserController::class, 'syncRoles'])->name('sync-roles');
        Route::post('{user}/avatar', [UserController::class, 'uploadAvatar'])->name('upload-avatar');
        Route::delete('{user}/avatar', [UserController::class, 'removeAvatar'])->name('remove-avatar');
    });

    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::post('{tenant}/logo', [TenantController::class, 'uploadLogo'])->name('upload-logo');
        Route::delete('{tenant}/logo', [TenantController::class, 'removeLogo'])->name('remove-logo');
    });

    // Rotas para busca e filtros avançados
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('roles', [RoleController::class, 'search'])->name('roles');
        Route::get('permissions', [PermissionController::class, 'search'])->name('permissions');
        Route::get('users', [UserController::class, 'search'])->name('users');
        Route::get('tenants', [TenantController::class, 'search'])->name('tenants');
        Route::get('addresses', [AddressController::class, 'search'])->name('addresses');
    });

    // Rotas para exportação
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('roles', [RoleController::class, 'export'])->name('roles');
        Route::get('permissions', [PermissionController::class, 'export'])->name('permissions');
        Route::get('users', [UserController::class, 'export'])->name('users');
        Route::get('tenants', [TenantController::class, 'export'])->name('tenants');
        Route::get('addresses', [AddressController::class, 'export'])->name('addresses');
    });

    // Rotas para importação
    Route::prefix('import')->name('import.')->group(function () {
        Route::post('roles', [RoleController::class, 'import'])->name('roles');
        Route::post('permissions', [PermissionController::class, 'import'])->name('permissions');
        Route::post('users', [UserController::class, 'import'])->name('users');
        Route::post('tenants', [TenantController::class, 'import'])->name('tenants');
        Route::post('addresses', [AddressController::class, 'import'])->name('addresses');
    });

    // Rotas para ações em lote
    Route::prefix('bulk')->name('bulk.')->group(function () {
        Route::post('roles/delete', [RoleController::class, 'bulkDelete'])->name('roles.delete');
        Route::post('roles/status', [RoleController::class, 'bulkUpdateStatus'])->name('roles.status');

        Route::post('permissions/delete', [PermissionController::class, 'bulkDelete'])->name('permissions.delete');
        Route::post('permissions/status', [PermissionController::class, 'bulkUpdateStatus'])->name('permissions.status');

        Route::post('users/delete', [UserController::class, 'bulkDelete'])->name('users.delete');
        Route::post('users/status', [UserController::class, 'bulkUpdateStatus'])->name('users.status');

        Route::post('tenants/delete', [TenantController::class, 'bulkDelete'])->name('tenants.delete');
        Route::post('tenants/status', [TenantController::class, 'bulkUpdateStatus'])->name('tenants.status');

        Route::post('addresses/delete', [AddressController::class, 'bulkDelete'])->name('addresses.delete');
        Route::post('addresses/status', [AddressController::class, 'bulkUpdateStatus'])->name('addresses.status');
    });
});
