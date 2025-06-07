<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Http\Controllers;

use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Enums\PermissionStatus;
use Callcocam\LaraGatekeeper\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PermissionController extends AbstractController
{
    protected ?string $model = Permission::class;

    protected string $resourceName = 'Permissão';
    protected string $pluralResourceName = 'Permissões'; 

    public function getSidebarMenuOrder(): int
    {
        return 15;
    }

    public function getSidebarMenuIconName(): string
    {
        return 'KeyRound';
    }

    protected function getFields(?Model $model = null): array
    {
        $isUpdate = $model && $model->exists;

        return [
            Field::make('name', 'Nome da Permissão')
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

            Field::make('status', 'Status')
                ->type('select')
                ->options(PermissionStatus::getOptions())
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
                ->options(PermissionStatus::variantOptions()),

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
                'options' => PermissionStatus::options(),
            ]
        ];
    }

    protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array
    {
        $permissionId = $model?->id;
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $isUpdate ? Rule::unique('permissions')->ignore($permissionId) : Rule::unique('permissions')],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_column(PermissionStatus::cases(), 'value'))],
        ];

        return $rules;
    }

    protected function getWithRelations(): array
    {
        return [];
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate($this->getValidationRules());

        $permission = $this->model::create($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' criado(a) com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $permission = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $permission));

        $permission->update($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' atualizado(a) com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $permission = $this->model::findOrFail($id);

        if ($permission->delete()) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' excluído(a) com sucesso.');
        } else {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Erro ao excluir ' . Str::singular($this->getResourceName()) . '.');
        }
    }
} 