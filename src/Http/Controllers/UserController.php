<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Http\Controllers;

use App\Models\User;
use Callcocam\LaraGatekeeper\Core\Support\Action;
use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Core\Support\Filters\SelectFilter;
use Callcocam\LaraGatekeeper\Core\Support\Table\Columns\StatusColumn;
use Callcocam\LaraGatekeeper\Enums\DefaultStatus;
use Callcocam\LaraGatekeeper\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends AbstractController
{
    protected ?string $model = User::class;



    protected string $resourceName = 'Usuário';
    protected string $pluralResourceName = 'Usuários';

    public function getSidebarMenuOrder(): int
    {
        return 20;
    }

    public function getSidebarMenuIconName(): string
    {
        return 'User';
    }

    protected function fields(?Model $model = null): array
    {
        $isUpdate = $model && $model->exists;

        return [
            Field::make('name', 'Nome Completo')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('email', 'E-mail')
                ->type('email')
                ->required()
                ->colSpan(6),

            Field::make('password', 'Senha')
                ->type('password')
                ->required(!$isUpdate)
                ->placeholder($isUpdate ? 'Deixe em branco para não alterar' : '')
                ->colSpan(6),

            Field::make('password_confirmation', 'Confirmar Senha')
                ->type('password')
                ->required(!$isUpdate)
                ->colSpan(6),

            Field::make('avatar', $isUpdate ? 'Alterar Avatar' : 'Avatar')
                ->type('filepond')
                ->accept('image/*')
                ->colSpan(12),

            Field::make('roles', 'Papéis')
                ->type('checkboxList')
                ->relationship('roles', 'name', 'id')
                ->options(Role::pluck('name', 'id')->toArray())
                ->gridCols(3)
                ->colSpan(12),

            Field::make('status', 'Status')
                ->type('select')
                ->options(DefaultStatus::getOptions())
                ->required()
                ->colSpan(12),
        ];
    }

    protected function columns(): array
    {
        $columns = [
            Column::make('avatar_url', 'Avatar')
                ->image(),
            Column::make('name', 'Nome')->sortable(),

            Column::make('email', 'E-mail')->sortable(),

            StatusColumn::withEnum(DefaultStatus::class)->sortable(),

            Column::actions([
                Action::view('admin.users'),
                Action::edit('admin.users'),
                Action::delete('admin.users'),
            ]),
        ];

        return $columns;
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'email'];
    }

    protected function filters(): array
    {
        return [
            SelectFilter::make('status', 'Status')
                ->options(DefaultStatus::options()),
        ];
    }

    protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array
    {
        $userId = $model?->id;
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $isUpdate ? Rule::unique('users')->ignore($userId) : Rule::unique('users')],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', Password::defaults(), 'confirmed'],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'avatar' => ['nullable'],
            'status' => ['required', Rule::in(array_column(DefaultStatus::cases(), 'value'))],
        ];

        return $rules;
    }

    protected function getWithRelations(): array
    {
        return ['roles'];
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate($this->getValidationRules());
        $roleIds = $validatedData['roles'] ?? [];
        $avatarTempPath = $validatedData['avatar'] ?? null;

        unset($validatedData['roles'], $validatedData['avatar']);

        if ($avatarTempPath) {
            $validatedData['avatar'] = $this->moveTemporaryFile($avatarTempPath, 'avatars');
        }

        $validatedData['password'] = bcrypt($validatedData['password']);
        $user = $this->model::create($validatedData);
        if ($user) {
            $user->roles()->sync($roleIds);
        }

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' criado(a) com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $user = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $user));
        $roleIds = $validatedData['roles'] ?? [];
        $avatarTempPath = $validatedData['avatar'] ?? null;

        unset($validatedData['roles'], $validatedData['avatar']);

        if ($request->filled('avatar')) {
            $newPath = null;
            if ($avatarTempPath) {
                $newPath = $this->moveTemporaryFile($avatarTempPath, 'avatars');
            }

            if ($newPath !== $user->avatar) {
                $oldAvatarPath = $user->avatar;
                $validatedData['avatar'] = $newPath;
                if ($oldAvatarPath && ($newPath || is_null($avatarTempPath))) {
                    Storage::disk(config('filesystems.default'))->delete($oldAvatarPath);
                }
            }
        }

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $user->update($validatedData);

        if ($roleIds) {
            $user->roles()->sync($roleIds);
        } else {
            $user->roles()->detach();
        }

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
        } else {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Erro ao excluir ' . Str::singular($this->getResourceName()) . '.');
        }
    }


    protected function getViewShow(): string
    {
        return "admin/users/Show";
    }
}
