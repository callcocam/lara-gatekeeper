<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Http\Controllers;

use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Enums\TenantStatus;
use Callcocam\LaraGatekeeper\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TenantController extends AbstractController
{
    protected ?string $model = Tenant::class;
    
    protected string $resourceName = 'Empresa';
    protected string $pluralResourceName = 'Empresas'; 

    public function getSidebarMenuOrder(): int
    {
        return 5;
    }

    public function getSidebarMenuIconName(): string
    {
        return 'Settings';
    }
 

    protected function fields(?Model $model = null): array
    {
        $isUpdate = $model && $model->exists;

        return [
            Field::make('name', 'Nome do Inquilino')
                ->type('text')
                ->required()
                ->colSpan(12),
 

            Field::make('domain', 'Domínio')
                ->type('text')
                ->colSpan(6),

            Field::make('email', 'E-mail')
                ->type('email')
                ->colSpan(6),

            Field::make('description', 'Descrição')
                ->type('textarea')
                ->colSpan(12),

            Field::make('logo', $isUpdate ? 'Alterar Logo' : 'Logo')
                ->type('filepond')
                ->accept('image/*')
                ->colSpan(12),

            Field::make('status', 'Status')
                ->type('select')
                ->options(TenantStatus::getOptions())
                ->required()
                ->colSpan(12),
        ];
    }

    protected function columns(): array
    {
        $columns = [
            Column::make('Logo')
                ->id('logo')
                ->accessorKey(null)
                ->hideable()
                ->html()
                ->cell(function (Tenant $row) {
                    $url = $row->logo ? Storage::url($row->logo) : null;
                    return $url ? '<img src="' . $url . '" alt="Logo" class="h-8 w-8 rounded object-cover">' : '-';
                }),

            Column::make('Nome', 'name')->sortable(),

            Column::make('Slug', 'slug')->sortable(),

            Column::make('Domínio', 'domain')->sortable(),

            Column::make('E-mail', 'email')->sortable(),

            Column::make('Criado em', 'created_at')
                ->sortable()
                ->formatter('formatDate')
                ->options('dd/MM/yyyy HH:mm'),

            Column::make('Status', 'status')
                ->sortable()
                ->formatter('renderBadge')
                ->options(TenantStatus::variantOptions()),

            Column::actions(),
        ];

        return $columns;
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'slug', 'domain', 'email'];
    }

    protected function filters(): array
    {
        return [
            [
                'column' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'options' => TenantStatus::options(),
            ]
        ];
    }

    protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array
    {
        $tenantId = $model?->id;
        $rules = [
            'name' => ['required', 'string', 'max:255'], 
            'domain' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable'],
            'status' => ['required', Rule::in(array_column(TenantStatus::cases(), 'value'))],
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
        $logoTempPath = $validatedData['logo'] ?? null;

        unset($validatedData['logo']);

        if ($logoTempPath) {
            $validatedData['logo'] = $this->moveTemporaryFile($logoTempPath, 'tenants/logos');
        }

        $tenant = $this->model::create($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' criado(a) com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $tenant = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $tenant));
        $logoTempPath = $validatedData['logo'] ?? null;

        unset($validatedData['logo']);

        if ($request->filled('logo')) {
            $newPath = null;
            if ($logoTempPath) {
                $newPath = $this->moveTemporaryFile($logoTempPath, 'tenants/logos');
            }

            if ($newPath !== $tenant->logo) {
                $oldLogoPath = $tenant->logo;
                $validatedData['logo'] = $newPath;
                if ($oldLogoPath && ($newPath || is_null($logoTempPath))) {
                    Storage::disk(config('filesystems.default'))->delete($oldLogoPath);
                }
            }
        }

        $tenant->update($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' atualizado(a) com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $tenant = $this->model::findOrFail($id);
        $logoPath = $tenant->logo;

        if ($tenant->delete()) {
            if ($logoPath) {
                Storage::disk(config('filesystems.default'))->delete($logoPath);
            }
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' excluído(a) com sucesso.');
        } else {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Erro ao excluir ' . Str::singular($this->getResourceName()) . '.');
        }
    }
} 