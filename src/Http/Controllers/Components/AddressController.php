<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Http\Controllers\Components;

use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Enums\AddressStatus;
use Callcocam\LaraGatekeeper\Http\Controllers\AbstractController;
use Callcocam\LaraGatekeeper\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AddressController extends AbstractController
{
    protected ?string $model = Address::class;

    public function getSidebarMenuOrder(): int
    {
        return 25;
    }

    public function getSidebarMenuIconName(): string
    {
        return __('Endereços');
    }

    protected function fields(?Model $model = null): array
    {
        $isUpdate = $model && $model->exists;

        return [
            Field::make('street', 'Logradouro')
                ->type('text')
                ->required()
                ->colSpan(8),

            Field::make('number', 'Número')
                ->type('text')
                ->required()
                ->colSpan(4),

            Field::make('complement', 'Complemento')
                ->type('text')
                ->colSpan(6),

            Field::make('neighborhood', 'Bairro')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('city', 'Cidade')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('state', 'Estado')
                ->type('text')
                ->required()
                ->colSpan(3),

            Field::make('zip_code', 'CEP')
                ->type('text')
                ->required()
                ->colSpan(3),

            Field::make('country', 'País')
                ->type('text')
                ->required()
                ->colSpan(6),

            Field::make('type', 'Tipo')
                ->type('select')
                ->options([
                    'residential' => 'Residencial',
                    'commercial' => 'Comercial',
                    'billing' => 'Cobrança',
                    'shipping' => 'Entrega',
                    'other' => 'Outro'
                ])
                ->required()
                ->colSpan(6),

            Field::make('status', 'Status')
                ->type('select')
                ->options(AddressStatus::getOptions())
                ->required()
                ->colSpan(12),
        ];
    }

    protected function columns(): array
    {
        $columns = [
            Column::make('Logradouro', 'street')->sortable(),

            Column::make('Número', 'number')->sortable(),

            Column::make('Bairro', 'neighborhood')->sortable(),

            Column::make('Cidade', 'city')->sortable(),

            Column::make('Estado', 'state')->sortable(),

            Column::make('CEP', 'zip_code')->sortable(),

            Column::make('Tipo', 'type')
                ->sortable()
                ->formatter('renderBadge')
                ->options([
                    'residential' => ['label' => 'Residencial', 'variant' => 'default'],
                    'commercial' => ['label' => 'Comercial', 'variant' => 'secondary'],
                    'billing' => ['label' => 'Cobrança', 'variant' => 'outline'],
                    'shipping' => ['label' => 'Entrega', 'variant' => 'destructive'],
                    'other' => ['label' => 'Outro', 'variant' => 'secondary']
                ]),

            Column::make('Criado em', 'created_at')
                ->sortable()
                ->formatter('formatDate')
                ->options('dd/MM/yyyy HH:mm'),

            Column::make('Status', 'status')
                ->sortable()
                ->formatter('renderBadge')
                ->options(AddressStatus::variantOptions()),

            Column::actions(),
        ];

        return $columns;
    }

    protected function getSearchableColumns(): array
    {
        return ['street', 'neighborhood', 'city', 'state', 'zip_code'];
    }

    protected function filters(): array
    {
        return [
            [
                'column' => 'type',
                'label' => 'Tipo',
                'type' => 'select',
                'options' => [
                    'residential' => 'Residencial',
                    'commercial' => 'Comercial',
                    'billing' => 'Cobrança',
                    'shipping' => 'Entrega',
                    'other' => 'Outro'
                ],
            ],
            [
                'column' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'options' => AddressStatus::options(),
            ]
        ];
    }

    protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array
    {
        $addressId = $model?->id;
        $rules = [
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['residential', 'commercial', 'billing', 'shipping', 'other'])],
            'status' => ['required', Rule::in(array_column(AddressStatus::cases(), 'value'))],
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

        $address = $this->model::create($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' criado(a) com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $address = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $address));

        $address->update($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' atualizado(a) com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $address = $this->model::findOrFail($id);

        if ($address->delete()) {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' excluído(a) com sucesso.');
        } else {
            return redirect()->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Erro ao excluir ' . Str::singular($this->getResourceName()) . '.');
        }
    }
} 