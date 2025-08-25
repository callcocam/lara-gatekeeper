<?php

/**
 * @package Callcocam\LaraGatekeeper\Http\Controllers
 */

namespace Callcocam\LaraGatekeeper\Http\Controllers;

use Callcocam\LaraGatekeeper\Traits\SortableWithRelationships;
use Illuminate\Routing\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\Inertia;
use Callcocam\LaraGatekeeper\Traits\ManagesSidebarMenu;
use Callcocam\LaraGatekeeper\Traits\Controllers\HasScreen;
use Callcocam\LaraGatekeeper\Traits\Controllers\ManagesResources;
use Callcocam\LaraGatekeeper\Traits\Controllers\ProcessesFieldsAndColumns;
use Callcocam\LaraGatekeeper\Traits\Controllers\HandlesFiltersAndSearch;
use Callcocam\LaraGatekeeper\Traits\Controllers\HasCrudHooks;
use Callcocam\LaraGatekeeper\Traits\Controllers\HandlesFileOperations;
use Callcocam\LaraGatekeeper\Traits\Controllers\HasDebugCustomFilters;
use Callcocam\LaraGatekeeper\Traits\Controllers\IteractWithTable;
use Callcocam\LaraGatekeeper\Traits\Controllers\ProvidesExtraData;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class AbstractController extends Controller
{
    use ManagesSidebarMenu,
        AuthorizesRequests,
        SortableWithRelationships,
        HasScreen,
        ManagesResources,
        ProcessesFieldsAndColumns,
        HandlesFiltersAndSearch,
        HasCrudHooks,
        HandlesFileOperations,
        ProvidesExtraData,
        HasDebugCustomFilters,
        IteractWithTable;

    protected $query;

    public function __construct(Request $request)
    {
        $this->initializeResourceNames();
        $this->middleware($this->getRouteMiddleware());

        $this->setRequest($request);
    }

    // Métodos abstratos que devem ser implementados pelos controllers filhos
    abstract protected function fields(?Model $model = null): array;
    abstract protected function columns(): array;
    abstract protected function filters(): array;
    abstract protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array;
    protected function getSearchableColumns(): array
    {
        $columns = $this->getColumns();
        $searchableColumns = array_map(function ($column) {
            if ($column->isSearchable()) {
                return $column->getName();
            }
            return null;
        }, $columns);
        return array_filter($searchableColumns);
    }

    /**
     * Método index principal usando as traits
     */
    public function index(Request $request): Response
    {
        $this->authorize($this->getSidebarMenuPermission('index'));

        // Iniciar query e carregar relacionamentos definidos
        $this->query = $this->model::query();
        $relationsToLoad = $this->getWithRelations();
        if (!empty($relationsToLoad)) {
            $this->query->with($relationsToLoad);
        }

        // Processar colunas usando a trait
        $tableColumns = $this->processTableColumns();
        $actions = $this->getTableActions();
        $filters = array_map(fn($filter) => $filter->toArray(), $this->filters());

        // Aplicar filtros, busca e ordenação
        $this->applyFilters($this->query, $request);
        $this->applyExtraFilters($this->query, $request);
        $searchableColumns = $this->applySearch($this->query, $request);
        $this->applySorting($this->query, $request, $tableColumns);

        return Inertia::render($this->getViewIndex(), [
            ...$this->toArray(),
            'columns' => $tableColumns,
            'filters' => $filters,
            'actions' => $actions,
            'importOptions' => $this->getImportOptions(),
            'exportOptions' => $this->getExportOptions(),
            'extraData' => $this->getExtraDataForIndex(),
            'fullWidth' => $this->getFullWidthIndexPage() ?? false,
            'currentFilters' => $request->query(),
            'queryParams' => $request->query(),
            'searchableColumns' => $searchableColumns,
            ...$this->getToArrayManagesResources(),
            ...$this->getToArrayHasDebugCustomFilters(),
            ...$this->getToArrayHasScreen()
        ]);
    }

    /**
     * Método create usando as traits
     */
    public function create(): Response
    {
        $this->authorize($this->getSidebarMenuPermission('create'));
        $fields = $this->processFields();

        return Inertia::render($this->getViewCreate(), [
            'fields' => $fields,
            'initialValues' => new $this->model(),
            ...$this->getToArrayManagesResources('create'),
            'extraData' => $this->getExtraDataForCreate(),
            'fullWidth' => $this->getFullWidthCreateForm() ?? false,
        ]);
    }

    /**
     * Método store usando as traits
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize($this->getSidebarMenuPermission('store'));
        $validatedData = $request->validate($this->getValidationRules());

        // Processar dados comuns (senha, user_id, etc.)
        $validatedData = $this->processCommonData($validatedData, $request, false);

        $model = $this->model::create($this->beforeStore($validatedData, $request));
        $this->afterStore($model, $validatedData, $request);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' criado(a) com sucesso.');
    }

    /**
     * Método show usando as traits
     */
    public function show(string $id): Response
    {
        $this->authorize($this->getSidebarMenuPermission('show'));
        $modelInstance = $this->model::findOrFail($id);

        if ($relationship = $this->getWithRelations()) {
            $modelInstance->load($relationship);
        }

        return Inertia::render($this->getViewShow(), [
            'model' => $modelInstance->toArray(),
            ...$this->getToArrayManagesResources('show', $modelInstance),
            'fullWidth' => $this->getFullWidthShowPage() ?? false,
        ]);
    }

    /**
     * Método edit usando as traits
     */
    public function edit(string $id): Response
    {
        $this->authorize($this->getSidebarMenuPermission('edit'));
        $modelInstance = $this->model::findOrFail($id);

        if ($relationship = $this->getWithRelations()) {
            $modelInstance->load($relationship);
        }

        $fields = $this->processFields($modelInstance);
        $initialValues = $this->getInitialValuesForEdit($modelInstance, $fields);

        return Inertia::render($this->getViewEdit(), [
            'fields' => $fields,
            'initialValues' => $initialValues,
            'modelId' => $id,
            'extraData' => $this->getExtraDataForEdit(),
            'fullWidth' => $this->getFullWidthEditForm() ?? false,
            ...$this->getToArrayManagesResources('edit', $modelInstance),
        ]);
    }

    /**
     * Método update usando as traits
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorize($this->getSidebarMenuPermission('update'));
        $modelInstance = $this->model::findOrFail($id);
        $validatedData = $this->getDataToUpdate(
            $request->validate($this->getValidationRules(true, $modelInstance)),
            $modelInstance
        );

        // Processar dados comuns (senha, user_id, etc.)
        $validatedData = $this->processCommonData($validatedData, $request, true);

        $modelInstance->update($this->beforeUpdate($validatedData, $request, $modelInstance));
        $this->afterUpdate($modelInstance, $validatedData, $request);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' atualizado(a) com sucesso.');
    }

    /**
     * Método destroy usando as traits
     */
    public function destroy(string $id): RedirectResponse
    {
        $this->authorize($this->getSidebarMenuPermission('destroy'));
        $modelInstance = $this->model::findOrFail($id);

        $this->beforeDestroy($modelInstance);
        $modelInstance->delete();
        $this->afterDestroy($modelInstance);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' excluído(a) com sucesso.');
    }
}
