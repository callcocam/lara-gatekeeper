<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Response;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'pageTitle' => 'Dashboard - Tenant',
            'pageDescription' => 'Painel de controle do tenant',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => '']
            ],
            'stats' => $this->getDashboardStats(),
        ]);
    }

    protected function getDashboardStats(): array
    {
        return [
            'users' => [
                'label' => 'Usuários',
                'value' => 0,
                'icon' => 'Users',
                'color' => 'blue'
            ],
            'roles' => [
                'label' => 'Funções',
                'value' => 0,
                'icon' => 'Shield',
                'color' => 'green'
            ],
            'permissions' => [
                'label' => 'Permissões',
                'value' => 0,
                'icon' => 'Key',
                'color' => 'purple'
            ],
        ];
    }
} 