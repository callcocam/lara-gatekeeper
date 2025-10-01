<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Http\Controllers;

use App\Models\Client;
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
            Field::make('client_id', 'Cliente')
                ->type('select')
                ->options(Client::pluck('name', 'id')->toArray())
                ->searchable()
                ->colSpan(12),

            Field::make('password', 'Senha')
                // ->type('password')
                ->required(!$isUpdate)
                ->placeholder($isUpdate ? 'Deixe em branco para não alterar' : '')
                ->colSpan(6),

            Field::make('password_confirmation', 'Confirmar Senha')
                // ->type('password')
                ->required(!$isUpdate)
                ->colSpan(6),

            // Field::make('avatar', $isUpdate ? 'Alterar Avatar' : 'Avatar')
            //     ->type('filepond')
            //     ->accept('image/*')
            //     ->colSpan(12),

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
            'roles' => ['nullable', 'array'],
            'client_id' => ['nullable', 'exists:clients,id'],
            // 'avatar' => ['nullable'],
            'status' => ['required', Rule::in(array_column(DefaultStatus::cases(), 'value'))],
        ];
        if ($isUpdate) {
            $rules['password'] = ['nullable', 'string', Password::defaults(), 'confirmed'];
        } else {
            $rules['password'] = ['required', 'string', Password::defaults(), 'confirmed'];
        }
        return $rules;
    }
    /**
     * Processa dados antes do store/update (senhas, user_id, etc.)
     */
    protected function processCommonData(array $validatedData, Request $request, bool $isUpdate = false): array
    {

        // Lógica para tratar senha
        if (empty($validatedData['password'])) {
            unset($validatedData['password']); // Remove senha vazia no update
        } else {
            $validatedData['password'] = bcrypt($validatedData['password']);
        }

        return $validatedData;
    }
    protected function getWithRelations(): array
    {
        return ['roles'];
    }


    /**
     * Hook executado antes de atualizar um modelo
     */
    protected function beforeUpdate(array $validatedData, Request $request, ?Model $modelInstance = null): array
    {

        return $validatedData;
    }

    /**
     * Hook executado após atualizar um modelo
     */
    protected function afterUpdate(?Model $model, array $validatedData, Request $request): void
    {
        $roleIds = $validatedData['roles'] ?? [];
        $model->roles()->sync($roleIds);
    }



    protected function getViewShow(): string
    {
        return "admin/users/Show";
    }
}
