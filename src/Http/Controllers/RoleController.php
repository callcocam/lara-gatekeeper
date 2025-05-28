<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Http\Controllers;

use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Enums\RoleStatus;
use Callcocam\LaraGatekeeper\Models\Permission;
use Callcocam\LaraGatekeeper\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends AbstractController
{
    protected ?string $model = Role::class;

    public function getSidebarMenuOrder(): int
    {
        return 10;
    }

    public function getSidebarMenuIconName(): string
    {
        return __('Papéis');
    }

    protected function getFields(?Model $model = null): array
    {
        $isUpdate = $model && $model->exists;

        return [
            Field::make('name', 'Nome do Papel')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('slug', 'Slug')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('description', 'Descrição')
                ->type('textarea')
                ->colSpan(12),

            Field::make('permissions', 'Permissões')
                ->type('checkboxList')
                ->relationship('permissions', 'name', 'id')
                ->options(Permission::pluck('name', 'id')->toArray())
                ->gridCols(3)
                ->colSpan(12),

            Field::make('status', 'Status')
                ->type('select')
                ->options(RoleStatus::getOptions())
                ->required()
                ->colSpan(12),
        ];
    }

    protected function getTableColumns(): array
    {
        $columns = [
            Column::make('Nome', 'name')->sortable(),

            Column::make('Slug', 'slug')->sortable(),

            Column::make('Descrição', 'description'),

            Column::make('Criado em', 'created_at')
                ->sortable()
                ->formatter('formatDate')
                ->options('dd/MM/yyyy HH:mm'),

            Column::make('Status', 'status')
                ->sortable()
                ->formatter('renderBadge')
                ->options(RoleStatus::options()),

            Column::actions(),
        ];

        return $columns;
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'slug', 'description'];
    }

    protected function getFilters(): array
    {
        return [
            [
                'column' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'options' => RoleStatus::options(),
            ]
        ];
    }

    protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array
    {
        $roleId = $model?->id;
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $isUpdate ? Rule::unique('roles')->ignore($roleId) : Rule::unique('roles')],
            'description' => ['nullable', 'string'],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'status' => ['required', Rule::in(array_column(RoleStatus::cases(), 'value'))],
        ];

        return $rules;
    }

    protected function getWithRelations(): array
    {
        return ['permissions'];
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate($this->getValidationRules());
        $permissionIds = $validatedData['permissions'] ?? [];

        unset($validatedData['permissions']);

        $role = $this->model::create($validatedData);
        if ($role && $permissionIds) {
            $role->permissions()->sync($permissionIds);
        }

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' criado(a) com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $role = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $role));
        $permissionIds = $validatedData['permissions'] ?? [];

        unset($validatedData['permissions']);

        $role->update($validatedData);
        
        if ($permissionIds) {
            $role->permissions()->sync($permissionIds);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' atualizado(a) com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $role = $this->model::findOrFail($id);

        if ($role->delete()) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' excluído(a) com sucesso.');
        } else {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Erro ao excluir ' . Str::singular($this->getResourceName()) . '.');
        }
    }
} 