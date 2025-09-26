<?php

/**
 * @package Callcocam\LaraGatekeeper\Http\Controllers
 */

namespace Callcocam\LaraGatekeeper\Http\Controllers;

use Callcocam\LaraGatekeeper\Core\Concerns\EvaluatesClosures;
use Callcocam\LaraGatekeeper\Core\Support\Form\Fields\TabsField;
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
use Illuminate\Http\JsonResponse;

abstract class AbstractController extends Controller
{
    use
        EvaluatesClosures,
        ManagesSidebarMenu,
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


    protected function formFieldValues(?array $initialValues = null, $fields = []): array
    {
        foreach ($fields as $field) {
            if ($field instanceof TabsField) {
                $initialValues[$field->getName()] = $field->getValue($initialValues);
            } else {
                $initialValues[$field->getName()] = $field->getValue($initialValues);
            }
        }
        return $initialValues;
    }

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
        $actions = array_values($this->getTableActions());
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

        $this->setContext('create');
        $actions = array_values($this->getFormActions());

        return Inertia::render($this->getViewCreate(), [
            'fields' => $fields,
            'initialValues' => new $this->model(),
            ...$this->getToArrayManagesResources('create'),
            'extraData' => $this->getExtraDataForCreate(),
            'fullWidth' => $this->getFullWidthCreateForm() ?? false,
            'actions' => $actions,
        ]);
    }

    protected function getRedirectRouteAfterStore(?Model $model = null): RedirectResponse
    {
        return redirect()->route($this->getRouteNameBase() . '.index')->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' criado(a) com sucesso.');
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

        return $this->getRedirectRouteAfterStore($model);
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

        $this->setContext('show');
        $actions = array_values($this->getFormActions($modelInstance));
        $fields = $this->processFields($modelInstance);

        return Inertia::render($this->getViewShow(), [
            'modelId' => $id,
            'fields' => $fields,
            'model' => $this->getInitialValuesForShow($modelInstance),
            'initialValues' => $modelInstance->toArray(),
            ...$this->getToArrayManagesResources('show', $modelInstance),
            'fullWidth' => $this->getFullWidthShowPage() ?? false,
            'actions' => $actions,
        ]);
    }

    protected function getRedirectRouteAfterEdit(?Model $model = null): RedirectResponse
    {
        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' atualizado(a) com sucesso.');
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
        $rawFields = $this->fields($modelInstance);
        $fields = $this->processFields($modelInstance, $rawFields);
        $initialValues = $this->getInitialValuesForEdit($modelInstance, $fields);

        $initialValues = $this->formFieldValues($initialValues, $rawFields);

        $this->setContext('edit');

        $actions = array_values($this->getFormActions($modelInstance));

        return Inertia::render($this->getViewEdit(), [
            'fields' => $fields,
            'initialValues' => $initialValues,
            'modelId' => $id,
            'extraData' => $this->getExtraDataForEdit(),
            'fullWidth' => $this->getFullWidthEditForm() ?? false,
            ...$this->getToArrayManagesResources('edit', $modelInstance),
            'actions' => $actions,
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

        return $this->getRedirectRouteAfterEdit($modelInstance);
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

        return $this->getRedirectRouteAfterDestroy($modelInstance);
    }

    protected function getRedirectRouteAfterDestroy(?Model $model = null): RedirectResponse
    {
        return $this->getRedirectRouteAfterDestroy($model)
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' excluído(a) com sucesso.');
    }

    // ==================== Métodos adicionais ====================

    public function bulkAction(Request $request): RedirectResponse
    {
        $this->authorize($this->getSidebarMenuPermission('bulk-action'));

        $action = $request->input('action');
        $selectedIds = $request->input('selectedIds', []);

        if (empty($action) || empty($selectedIds) || !is_array($selectedIds)) {
            return redirect()->back()->with('error', 'Ação ou IDs inválidos para ação em massa.');
        }

        $models = $this->model::whereIn('id', $selectedIds)->get();

        foreach ($models as $model) {
            // Verifica permissão para cada modelo individualmente
            if (Gate::allows($this->getSidebarMenuPermission('bulk-action'), $model)) {
                // Chama o método correspondente à ação, se existir
                if (method_exists($this, $action)) {
                    $this->$action($model);
                }
            }
        }

        return redirect()->back()->with('success', 'Ação em massa executada com sucesso.');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize($this->getSidebarMenuPermission('import'));

        $this->getTableActions();

        $action = $this->getAction('import');

        $result = $this->evaluate($action->getCallback(), ['request' => $request]);
        if ($result instanceof RedirectResponse) {
            return $result;
        }
        return redirect()->back()->with('error', 'Import action did not return a valid response.');
    }

    public function export(Request $request): mixed
    {
        $this->authorize($this->getSidebarMenuPermission('export'));

        $this->getTableActions();

        $action = $this->getAction('export');

        $query = $this->model::query();

        $this->applyFilters($query, $request);

        $this->applySearch($query, $request);

        $this->applyExtraFilters($query, $request);

        $result = $this->evaluate($action->getCallback(), ['query' => $query]);

        if ($result) {
            return $result;
        }
        return redirect()->back()->with('error', 'Export action did not return a valid response.');
    }

    public function cascading(Request $request): JsonResponse
    {


        return response()->json($request->all());
    }
}
