<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Http\Controllers\Tenant;

use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Models\Auth\User;
use Callcocam\LaraGatekeeper\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends AbstractController
{
    protected ?string $model = User::class;
    
    protected string $resourceName = 'Usuário';
    protected string $pluralResourceName = 'Usuários';

    public function getSidebarMenuOrder(): int
    {
        return 1;
    }

    public function getSidebarMenuIconName(): string
    {
        return 'Users';
    }

    protected function getFields(?Model $model = null): array
    {
        $isUpdate = $model && $model->exists;

        return [
            Field::make('name', 'Nome')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('email', 'E-mail')
                ->type('email')
                ->required()
                ->colSpan(6),

            Field::make('password', $isUpdate ? 'Nova Senha' : 'Senha')
                ->type('password')
                ->required(!$isUpdate)
                ->colSpan(6),

            Field::make('password_confirmation', 'Confirmar Senha')
                ->type('password')
                ->required(!$isUpdate)
                ->colSpan(6),

            Field::make('avatar', $isUpdate ? 'Alterar Avatar' : 'Avatar')
                ->type('filepond')
                ->accept('image/*')
                ->colSpan(12),

            Field::make('roles', 'Funções')
                ->type('select')
                ->multiple()
                ->options(Role::pluck('name', 'id')->toArray())
                ->colSpan(12),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Column::make('Avatar')
                ->id('avatar')
                ->accessorKey(null)
                ->hideable()
                ->html()
                ->cell(function (User $row) {
                    $url = $row->avatar ? Storage::disk(config('filesystems.default'))->url($row->avatar) : null;
                    return $url ? '<img src="' . $url . '" alt="Avatar" class="h-8 w-8 rounded-full object-cover">' : 
                                 '<div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-medium">' . 
                                 strtoupper(substr($row->name, 0, 2)) . '</div>';
                }),

            Column::make('Nome', 'name')->sortable(),
            Column::make('E-mail', 'email')->sortable(),
            
            Column::make('Funções', 'roles')
                ->formatter('renderBadges')
                ->cell(function (User $row) {
                    return $row->roles->pluck('name')->join(', ');
                }),

            Column::make('Criado em', 'created_at')
                ->sortable()
                ->formatter('formatDate')
                ->options('dd/MM/yyyy HH:mm'),

            Column::actions(),
        ];
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'email'];
    }

    protected function getFilters(): array
    {
        return [
            [
                'column' => 'roles',
                'label' => 'Função',
                'type' => 'select',
                'options' => Role::pluck('name', 'id')->toArray(),
            ]
        ];
    }

    protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array
    {
        $userId = $model?->id;
        
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'avatar' => ['nullable'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ];

        if (!$isUpdate) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    protected function getWithRelations(): array
    {
        return ['roles'];
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate($this->getValidationRules());
        $avatarTempPath = $validatedData['avatar'] ?? null;
        $roles = $validatedData['roles'] ?? [];

        unset($validatedData['avatar'], $validatedData['roles']);
        
        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        if ($avatarTempPath) {
            $validatedData['avatar'] = $this->moveTemporaryFile($avatarTempPath, 'users/avatars');
        }

        $user = $this->model::create($validatedData);
        
        if (!empty($roles)) {
            $user->roles()->sync($roles);
        }

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' criado(a) com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $user = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $user));
        $avatarTempPath = $validatedData['avatar'] ?? null;
        $roles = $validatedData['roles'] ?? [];

        unset($validatedData['avatar'], $validatedData['roles']);

        if (isset($validatedData['password']) && !empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        if ($request->filled('avatar')) {
            $newPath = null;
            if ($avatarTempPath) {
                $newPath = $this->moveTemporaryFile($avatarTempPath, 'users/avatars');
            }

            if ($newPath !== $user->avatar) {
                $oldAvatarPath = $user->avatar;
                $validatedData['avatar'] = $newPath;
                if ($oldAvatarPath && ($newPath || is_null($avatarTempPath))) {
                    Storage::disk(config('filesystems.default'))->delete($oldAvatarPath);
                }
            }
        }

        $user->update($validatedData);
        $user->roles()->sync($roles);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' atualizado(a) com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $user = $this->model::findOrFail($id);
        $avatarPath = $user->avatar;

        if ($user->delete()) {
            if ($avatarPath) {
                Storage::disk(config('filesystems.default'))->delete($avatarPath);
            }
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' excluído(a) com sucesso.');
        }

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('error', 'Erro ao excluir ' . Str::lower($this->getResourceName()) . '.');
    }
} 