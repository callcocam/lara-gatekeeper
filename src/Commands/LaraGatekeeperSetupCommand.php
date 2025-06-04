<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Commands;
 
use App\Models\User; 
use Callcocam\LaraGatekeeper\Enums\PermissionStatus;
use Callcocam\LaraGatekeeper\Enums\UserStatus;
use Callcocam\LaraGatekeeper\Models\Permission;
use Callcocam\LaraGatekeeper\Models\Role;
use Callcocam\LaraGatekeeper\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class LaraGatekeeperSetupCommand extends Command
{
    public $signature = 'lara-gatekeeper:setup';

    public $description = 'Configura recursos iniciais para o LaraGatekeeper, como tenants, usuários, funções e permissões.';

    public function handle(): int
    {
        $this->comment('Iniciando configuração do LaraGatekeeper');

        if (!$this->confirm('Deseja criar um tenant, usuário, funções e permissões?')) {
            return self::SUCCESS;
        }

        // Gerenciamento de Tenant
        $tenant = $this->manageTenant();

        // Gerenciamento de Usuário
        $user = $this->manageUser($tenant);

        // Gerenciamento de Roles
        if ($this->confirm('Deseja gerenciar funções (roles)?')) {
            $this->manageRole($user);
        }

        // Gerenciamento de Permissões
        if ($this->confirm('Deseja criar permissões baseadas nas rotas?')) {
            $this->createPermission();
        }

        $this->comment('Configuração concluída com sucesso!');

        return self::SUCCESS;
    }

    /**
     * Gerencia Tenants - permite selecionar existente ou criar novo
     */
    protected function manageTenant()
    {
        $tenants = Tenant::all();

        if ($tenants->count()) {
            $this->info('Tenants existentes encontrados: ' . $tenants->count());

            if ($this->confirm('Deseja criar um novo tenant?')) {
                return $this->createTenant();
            } else {
                $tenantId = $this->choice('Qual tenant você deseja utilizar?', Tenant::pluck('name', 'id')->toArray());
                return Tenant::find($tenantId);
            }
        } else {
            $this->info('Nenhum tenant encontrado.');
            return $this->createTenant();
        }
    }

    /**
     * Gerencia Usuários - permite selecionar existente ou criar novo
     */
    protected function manageUser($tenant)
    {
        $users = User::all();

        if ($users->count()) {
            $this->info('Usuários existentes encontrados: ' . $users->count());

            if ($this->confirm('Deseja criar um novo usuário?')) {
                return $this->createUsers($tenant);
            } else {
                $userId = $this->choice('Qual usuário você deseja utilizar?', User::pluck('name', 'id')->toArray());
                return User::find($userId);
            }
        } else {
            $this->info('Nenhum usuário encontrado.');
            return $this->createUsers($tenant);
        }
    }

    /**
     * Gerencia Roles - permite criar múltiplas roles
     */
    protected function manageRole($user)
    {
        $roles = Role::all();

        if ($roles->count()) {
            $this->info('Funções (roles) existentes encontradas: ' . $roles->count());

            if ($this->confirm('Deseja associar o usuário a uma role existente?')) {
                $roleId = $this->choice('Qual função (role) você deseja associar?', Role::pluck('name', 'id')->toArray());
                $role = Role::find($roleId);

                if ($user) {
                    $user->assignRole($role);
                    $this->info("Usuário associado à função '{$role->name}' com sucesso!");
                }
            }
        }

        if ($this->confirm('Deseja criar uma nova função (role)?')) {
            $roleName = $this->ask('Qual o nome da função (role) que deseja criar?', 'Super Admin');
            $isAdministrator = $this->confirm('Esta função é de administrador?');

            if ($isAdministrator) {
                $this->createRole($roleName, $user, true);
            } else {
                $this->createRole($roleName, $user);
            }
        }
    }

    protected function createTenant()
    {
        $this->comment('Criando tenant');

        $name = $this->ask('Qual o nome do tenant?', fake()->company);
        $domain = $this->ask('Qual o domínio do tenant?', request()->getHost());
        $email = $this->ask('Qual o email do tenant?', fake()->email);
        $status = $this->choice('Qual o status do tenant?', ['published', 'draft'], 'published');

        $tenant = Tenant::create([
            'name' => $name,
            'domain' => $domain,
            'email' => $email,
            'status' => $status,
        ]);

        $this->info("Tenant `{$name}` criado com sucesso.");

        return $tenant;
    }

    protected function createUsers($tenant = null)
    {
        $this->comment('Criando usuário');

        $name = $this->ask('Qual o nome do usuário?', 'Admin');
        $email = $this->ask('Qual o email do usuário?', sprintf('admin@%s', request()->getHost()));
        $status = $this->choice('Qual o status do usuário?', UserStatus::getOptions(), UserStatus::Published->value);

        if (User::where('email', $email)->count()) {
            $this->error('Usuário já existe');
            return $this->manageUser($tenant);
        }

        if (!$tenant) {
            $tenant = $this->manageTenant();
        }

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'status' => $status,
        ]);

        // $user->tenant()->associate($tenant);
        $user->save();

        $this->info("Usuário `{$name}` criado com sucesso.");

        return $user;
    }

    protected function createRole($role, $user = null, $permission = false)
    {
        $this->comment("Criando função (role) `{$role}`");

        if (Role::where('slug', str($role)->slug())->exists()) {
            $this->error("Função (role) `{$role}` já existe.");
            return;
        }

        $newRole = Role::create([
            'name' => $role,
            'slug' => str($role)->slug(),
            'description' => "Função para {$role}",
            'special' => $permission
        ]);
        if ($user) {
            $user->roles()->sync([$newRole->id]);
            $this->info("Usuário associado à função '{$role}' com sucesso!");
        }

        $this->info("Função (role) `{$role}` criada com sucesso.");

        if ($this->confirm('Criar outra função (role)?')) {
            $roleName = $this->ask('Qual o nome da função (role) que deseja criar?', 'Super Admin');
            $isAdministrator = $this->confirm('Esta função é de administrador?');

            if ($isAdministrator) {
                $this->createRole($roleName, $user, true);
            } else {
                $this->createRole($roleName, $user);
            }
        }
    }

    protected function createPermission()
    {
        $this->comment("Criando permissões baseadas nas rotas do sistema...");

        $routes = Route::getRoutes();
        $permissions = [];
        $count = 0;

        foreach ($routes as $route) {
            if (isset($route->action['as'])) {
                $name = str_replace('.', ' ', $route->action['as']);
                // Ignora rotas que não devem gerar permissões
                if (in_array($route->getName(), ['login', 'logout', 'register', 'password.request', 'password.email', 'password.reset', 'verification.notice', 'verification.verify', 'verification.send', 'sanctum.csrf-cookie'])) {
                    continue;
                }
                $name = ucwords($name);

                $slug = $route->action['as'];

                if (Permission::where('slug', $slug)->count()) {
                    continue;
                }

                Permission::create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => "Permissão para {$name}",
                    'status' => PermissionStatus::Published->value,
                ]);

                $count++;
            }
        }

        $this->info("Total de {$count} permissões criadas com sucesso.");
    }
}
