<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Http\Controllers\Landlord;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Response;
use Inertia\Inertia;
use Callcocam\LaraGatekeeper\Models\Tenant;
use Callcocam\LaraGatekeeper\Models\Auth\User;
use Callcocam\LaraGatekeeper\Models\Role;
use Callcocam\LaraGatekeeper\Models\Permission;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Landlord/Dashboard', [
            'pageTitle' => 'Dashboard - Landlord',
            'pageDescription' => 'Painel de controle global - Gerenciamento de todos os tenants',
            'breadcrumbs' => [
                ['title' => 'Dashboard Landlord', 'href' => '']
            ],
            'stats' => $this->getDashboardStats(),
            'charts' => $this->getChartsData(),
            'recentActivity' => $this->getRecentActivity(),
            'systemHealth' => $this->getSystemHealth(),
            'quickActions' => $this->getQuickActions(),
            'isLandlord' => true,
        ]);
    }

    protected function getDashboardStats(): array
    {
        return [
            'tenants' => [
                'label' => 'Tenants',
                'value' => Tenant::count(),
                'icon' => 'Building',
                'color' => 'blue',
                'description' => 'Total de empresas/tenants'
            ],
            'users' => [
                'label' => 'Usuários (Global)',
                'value' => User::count(),
                'icon' => 'Users',
                'color' => 'green',
                'description' => 'Total de usuários em todos os tenants'
            ],
            'roles' => [
                'label' => 'Funções',
                'value' => Role::count(),
                'icon' => 'Shield',
                'color' => 'purple',
                'description' => 'Total de funções/roles'
            ],
            'permissions' => [
                'label' => 'Permissões',
                'value' => Permission::count(),
                'icon' => 'Key',
                'color' => 'orange',
                'description' => 'Total de permissões'
            ],
            'active_tenants' => [
                'label' => 'Tenants Ativos',
                'value' => Tenant::where('status', 'active')->count(),
                'icon' => 'CheckCircle',
                'color' => 'emerald',
                'description' => 'Tenants com status ativo'
            ],
            'inactive_tenants' => [
                'label' => 'Tenants Inativos',
                'value' => Tenant::where('status', 'inactive')->count(),
                'icon' => 'XCircle',
                'color' => 'red',
                'description' => 'Tenants com status inativo'
            ],
        ];
    }

    protected function getChartsData(): array
    {
        return [
            'tenants_growth' => [
                'title' => 'Crescimento de Tenants',
                'data' => $this->getTenantsGrowthData(),
                'type' => 'line'
            ],
            'users_by_tenant' => [
                'title' => 'Usuários por Tenant',
                'data' => $this->getUsersByTenantData(),
                'type' => 'bar'
            ],
            'plan_distribution' => [
                'title' => 'Distribuição de Planos',
                'data' => $this->getPlanDistributionData(),
                'type' => 'pie'
            ],
        ];
    }

    protected function getTenantsGrowthData(): array
    {
        // Últimos 12 meses
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Tenant::where('created_at', '<=', $date->endOfMonth())->count();
            $data[] = [
                'month' => $date->format('M Y'),
                'tenants' => $count
            ];
        }
        return $data;
    }

    protected function getUsersByTenantData(): array
    {
        return Tenant::withCount('users')
            ->orderBy('users_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($tenant) {
                return [
                    'tenant' => $tenant->name,
                    'users' => $tenant->users_count
                ];
            })->toArray();
    }

    protected function getPlanDistributionData(): array
    {
        return Tenant::selectRaw('plan, COUNT(*) as count')
            ->groupBy('plan')
            ->get()
            ->map(function ($item) {
                return [
                    'plan' => ucfirst($item->plan ?? 'Não definido'),
                    'count' => $item->count
                ];
            })->toArray();
    }

    protected function getRecentActivity(): array
    {
        $activities = [];

        // Tenants criados recentemente
        $recentTenants = Tenant::latest()
            ->limit(5)
            ->get();

        foreach ($recentTenants as $tenant) {
            $activities[] = [
                'type' => 'tenant_created',
                'title' => "Novo tenant criado: {$tenant->name}",
                'description' => "Status: {$tenant->status}",
                'time' => $tenant->created_at->diffForHumans(),
                'icon' => 'Building2',
                'color' => 'blue'
            ];
        }

        // Usuários criados recentemente
        $recentUsers = User::latest()
            ->limit(5)
            ->get();

        foreach ($recentUsers as $user) {
            $activities[] = [
                'type' => 'user_created',
                'title' => "Novo usuário: {$user->name}",
                'description' => "Email: {$user->email}",
                'time' => $user->created_at->diffForHumans(),
                'icon' => 'UserPlus',
                'color' => 'green'
            ];
        }

        // Ordenar por data (mais recente primeiro)
        return collect($activities)
            ->sortByDesc('time')
            ->take(10)
            ->values()
            ->toArray();
    }

    protected function getSystemHealth(): array
    {
        return [
            'database' => [
                'status' => 'healthy',
                'response_time' => '2ms',
                'connections' => 15
            ],
            'storage' => [
                'status' => 'healthy',
                'used_space' => '2.4GB',
                'available_space' => '47.6GB'
            ],
            'cache' => [
                'status' => 'healthy',
                'hit_rate' => '94%',
                'memory_usage' => '128MB'
            ],
            'queue' => [
                'status' => 'healthy',
                'pending_jobs' => 3,
                'failed_jobs' => 0
            ]
        ];
    }

    protected function getQuickActions(): array
    {
        return [
            [
                'title' => 'Criar Novo Tenant',
                'description' => 'Adicionar um novo tenant ao sistema',
                'icon' => 'Plus',
                'color' => 'blue',
                'route' => 'landlord.tenants.create'
            ],
            [
                'title' => 'Gerenciar Usuários',
                'description' => 'Visualizar e gerenciar todos os usuários',
                'icon' => 'Users',
                'color' => 'green',
                'route' => 'landlord.users.index'
            ],
            [
                'title' => 'Configurar Permissões',
                'description' => 'Gerenciar permissões globais do sistema',
                'icon' => 'Key',
                'color' => 'purple',
                'route' => 'landlord.permissions.index'
            ],
            [
                'title' => 'Relatórios',
                'description' => 'Visualizar relatórios e estatísticas',
                'icon' => 'BarChart3',
                'color' => 'orange',
                'route' => 'landlord.reports.index'
            ]
        ];
    }

    public function tenantsOverview(Request $request): Response
    {
        $tenants = Tenant::with(['users'])
            ->withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Landlord/TenantsOverview', [
            'pageTitle' => 'Visão Geral dos Tenants',
            'pageDescription' => 'Resumo de todos os tenants e suas estatísticas',
            'breadcrumbs' => [
                ['title' => 'Dashboard Landlord', 'href' => route('landlord.dashboard')],
                ['title' => 'Visão Geral dos Tenants', 'href' => '']
            ],
            'tenants' => $tenants,
            'isLandlord' => true,
        ]);
    }
} 