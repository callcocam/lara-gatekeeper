<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Lara Gatekeeper Web Routes - Compatibilidade
|--------------------------------------------------------------------------
|
| DEPRECATED: Estas rotas são mantidas para compatibilidade.
| Use as rotas específicas em landlord.php e tenant.php
|
*/

// Redirecionar rotas antigas para o novo sistema
Route::prefix('admin')->name('admin.')->middleware([
    'web',
    'auth',
])->group(function () {
    
    // Redirecionar para landlord ou tenant baseado no contexto
    Route::get('/', function () {
        // Verificar se o usuário é landlord
        if (auth()->user() && auth()->user()->is_landlord) {
            return redirect()->route('landlord.dashboard');
        }
        
        // Redirecionar para tenant
        return redirect()->route('tenant.dashboard');
    })->name('dashboard');

    // Redirecionamentos para manter compatibilidade
    Route::any('{path}', function ($path) {
        // Verificar se o usuário é landlord
        if (auth()->user() && auth()->user()->is_landlord) {
            return redirect()->route('landlord.dashboard');
        }
        
        // Redirecionar para tenant
        return redirect()->route('tenant.dashboard');
    })->where('path', '.*');
});
