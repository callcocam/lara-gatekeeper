<?php

/**
 * @package Callcocam\LaraGatekeeper\Http\Controllers\Landlord
 */

namespace Callcocam\LaraGatekeeper\Http\Controllers\Landlord;

use Callcocam\LaraGatekeeper\Core\Support\Action;
use Illuminate\Routing\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\Inertia;
use Callcocam\LaraGatekeeper\Traits\ManagesSidebarMenu;
use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


abstract class AbstractController extends Controller
{
    use ManagesSidebarMenu, AuthorizesRequests;

    protected ?string $model = null;
    protected string $resourceName = '';
    protected string $pluralResourceName = '';
    protected string $routeNameBase = '';
    protected string $viewPrefix = 'landlord/crud';

    protected array $defaultBreadcrumbs = [];
    protected string $pageTitle = '';

    public function __construct()
    {
        $this->initializeResourceNames();
        $this->middleware($this->getRouteMiddleware());
    }

    protected function initializeResourceNames(): void
    {
        if ($this->model) {
            $baseName = class_basename($this->model);
            if ($this->resourceName == '') {
                $this->resourceName = Str::snake($baseName);
            }
            if ($this->pluralResourceName == '') {
                $this->pluralResourceName = Str::plural($this->resourceName);
            }
            if ($this->routeNameBase == '') {
                $this->routeNameBase = 'landlord.' . str($baseName)->snake()->plural()->toString();
            }
        } else {
            throw new \Exception('A propriedade $model deve ser definida no controller filho.');
        }
    }

    protected function getResourceName(): string
    {
        return __($this->resourceName);
    }

    protected function getPluralResourceName(): string
    {
        return __($this->pluralResourceName);
    }

    protected function getRouteNameBase(): string
    {
        return __($this->routeNameBase);
    }

    protected function generatePageTitle(string $action, ?Model $modelInstance = null): string
    {
        $resourceTitle = Str::ucfirst(str_replace('_', ' ', $this->getPluralResourceName()));
        $title = null;
        switch ($action) {
            case 'index':
                $title = sprintf('Gerenciar %s - Landlord', $resourceTitle);
                break;
            case 'create':
                $title = sprintf('Cadastrar %s - Landlord', Str::singular($resourceTitle));
                break;
            case 'edit':
                $identifier = $modelInstance ? ($modelInstance->name ?? $modelInstance->id) : '';
                $title = sprintf('Editar %s - Landlord', Str::singular($resourceTitle)) . ($identifier ? ": {$identifier}" : '');
                break;
            case 'show':
                $identifier = $modelInstance ? ($modelInstance->name ?? $modelInstance->id) : '';
                $title = sprintf('Detalhes de %s - Landlord', Str::singular($resourceTitle)) . ($identifier ? ": {$identifier}" : '');
                break;
            default:
                $title = $resourceTitle . ' - Landlord';
        }
        return __($title);
    }

    protected function generatePageDescription(string $action, ?Model $modelInstance = null): string
    {
        return 'Gerenciamento global de todos os tenants - Acesso Landlord';
    }

    protected function generateDefaultBreadcrumbs(string $action, ?Model $modelInstance = null): array
    {
        $pluralTitle = $this->getPluralResourceName();
        $singularTitle = Str::singular($pluralTitle);
        $indexRoute = route($this->getRouteNameBase() . '.index');

        $breadcrumbs = [
            ['title' => 'Dashboard Landlord', 'href' => route('landlord.dashboard')],
            ['title' => $pluralTitle, 'href' => $indexRoute],
        ];
        switch ($action) {
            case 'create':
                $breadcrumbs[] = ['title' => "Cadastrar Novo {$singularTitle}", 'href' => ''];
                break;
            case 'edit':
                $identifier = $modelInstance ? ($modelInstance->name ?? $modelInstance->id) : '';
                $breadcrumbs[] = ['title' => "Editar {$singularTitle}" . ($identifier ? ": {$identifier}" : ''), 'href' => ''];
                break;
            case 'show':
                $identifier = $modelInstance ? ($modelInstance->name ?? $modelInstance->id) : '';
                $breadcrumbs[] = ['title' => "Detalhes" . ($identifier ? ": {$identifier}" : ''), 'href' => ''];
                break;
        }

        return $breadcrumbs;
    }

    protected function getRouteMiddleware(): array
    {
        return ['auth:landlord'];
    }

    abstract protected function getFields(?Model $model = null): array;
    abstract protected function getTableColumns(): array;
    abstract protected function getFilters(): array;
    abstract protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array;
    abstract protected function getSearchableColumns(): array;
    
    protected function beforeStore(array $validatedData, Request $request): array
    {
        return $validatedData;
    }
    
    protected function afterStore(?Model $model, array $validatedData, Request $request): void
    {
        //
    }
    
    protected function beforeUpdate(array $validatedData, Request $request, ?Model $modelInstance = null): array
    {
        return $validatedData;
    }
    
    protected function afterUpdate(?Model $model, array $validatedData, Request $request): void
    {
        //
    }
    
    protected function beforeDestroy(Model $modelInstance): void
    {
        //
    }
    
    protected function afterDestroy(Model $modelInstance): void
    {
        //
    }

    protected function getImportOptions(): array
    {
        return [
            //
        ];
    }
    
    /**
     * Define as ações padrão para a tabela.
     * Pode ser sobrescrito por controllers filhos para lógica customizada.
     */
    protected function getTableActions(): array
    {
        $actions = [];
        if (Gate::allows($this->getSidebarMenuPermission('show'))) {
            $actions[] = Action::make('show')
                ->icon('Eye')
                ->color('success')
                ->routeNameBase($this->getRouteNameBase())
                ->routeSuffix('show')
                ->setIsHtml(false)
                ->header('Visualizar')
                ->accessorKey('show');
        }
        if (Gate::allows($this->getSidebarMenuPermission('edit'))) {
            $actions[] = Action::make('edit')
                ->icon('PenSquare')
                ->color('primary')
                ->routeNameBase($this->getRouteNameBase())
                ->routeSuffix('edit')
                ->setIsHtml(false)
                ->header('Editar')
                ->accessorKey('edit');
        }
        if (Gate::allows($this->getSidebarMenuPermission('destroy'))) {
            $actions[] = Action::make('destroy')
                ->icon('Trash')
                ->color('danger')
                ->routeNameBase($this->getRouteNameBase())
                ->routeSuffix('destroy')
                ->setIsHtml(false)
                ->header('Excluir')
                ->accessorKey('destroy');
        }
        return $actions;
    }

    protected function getActions(): array
    {
        $actions = [];
        if (Gate::allows($this->getSidebarMenuPermission('create'))) {
            $actions[] = Action::make('create')
                ->icon('Plus')
                ->color('primary')
                ->routeNameBase($this->getRouteNameBase())
                ->routeSuffix('create')
                ->setIsHtml(false)
                ->header('Cadastrar')
                ->accessorKey('create');
        }
        return $actions;
    }

    protected function getWithRelations(): array
    {
        return [];
    }

    protected function getTableDefaultSortColumn(): ?string
    {
        return 'created_at';
    }

    protected function getDataToUpdate(array $validatedData, Model $modelInstance): array
    {
        $data = [];
        foreach ($validatedData as $key => $value) {
            if ($modelInstance->isFillable($key)) {
                $data[$key] = $value;
            }
        }
        return $data;
    }

    protected function processFields(?Model $model = null): array
    {
        $fields = $this->getFields($model);
        return collect($fields)->map(function ($field) use ($model) {
            if ($field instanceof Field) {
                return $field->toArray();
            }
            return $field;
        })->toArray();
    }

    protected function getViewIndex(): string
    {
        return $this->viewPrefix . '/index';
    }

    protected function getViewCreate(): string
    {
        return $this->viewPrefix . '/create';
    }

    protected function getViewShow(): string
    {
        return $this->viewPrefix . '/show';
    }

    protected function getViewEdit(): string
    {
        return $this->viewPrefix . '/edit';
    }

    public function index(Request $request): Response
    {
        $this->authorize($this->getSidebarMenuPermission('index'));

        $query = $this->model::query();

        // Aplicar relacionamentos
        if (!empty($this->getWithRelations())) {
            $query->with($this->getWithRelations());
        }

        // Aplicar filtros
        $filters = $this->getFilters();
        foreach ($filters as $filter) {
            if (is_array($filter) && isset($filter['column'])) {
                $this->applyFilter($query, $request, $filter);
            }
        }

        // Aplicar busca
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $searchableColumns = $this->getSearchableColumns();
            
            if (!empty($searchableColumns)) {
                $query->where(function ($q) use ($searchTerm, $searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'like', "%{$searchTerm}%");
                    }
                });
            }
        }

        // Aplicar ordenação
        $sortColumn = $request->get('sort', $this->getTableDefaultSortColumn());
        $sortDirection = $request->get('direction', 'desc');
        
        if ($sortColumn) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        // Paginação
        $perPage = $request->get('per_page', 15);
        $data = $query->paginate($perPage);

        // Processar colunas da tabela
        $columns = collect($this->getTableColumns())->map(function ($column) {
            if ($column instanceof Column) {
                return $column->toArray();
            }
            return $column;
        })->toArray();

        return Inertia::render($this->getViewIndex(), [
            'data' => $data,
            'columns' => $columns,
            'actions' => $this->getTableActions(),
            'pageActions' => $this->getActions(),
            'filters' => $this->getFilters(),
            'breadcrumbs' => $this->generateDefaultBreadcrumbs('index'),
            'pageTitle' => $this->generatePageTitle('index'),
            'pageDescription' => $this->generatePageDescription('index'),
            'searchableColumns' => $this->getSearchableColumns(),
            'importOptions' => $this->getImportOptions(),
            'isLandlord' => true, // Identificador para o frontend
            ...$this->getExtraDataForIndex(),
        ]);
    }

    protected function applyFilter($query, Request $request, array $filter): void
    {
        $column = $filter['column'];
        $value = $request->get($column);
        
        if ($value !== null && $value !== '') {
            $query->where($column, $value);
        }
    }

    protected function getExtraDataForIndex(): array
    {
        return [];
    }

    public function create(): Response
    {
        $this->authorize($this->getSidebarMenuPermission('create'));

        $modelInstance = new $this->model();
        $fields = $this->processFields($modelInstance);

        return Inertia::render($this->getViewCreate(), [
            'fields' => $fields,
            'breadcrumbs' => $this->generateDefaultBreadcrumbs('create'),
            'pageTitle' => $this->generatePageTitle('create'),
            'pageDescription' => $this->generatePageDescription('create'),
            'initialValues' => $this->getInitialValuesForCreate($modelInstance, $fields),
            'isLandlord' => true,
            ...$this->getExtraDataForCreate(),
        ]);
    }

    protected function getExtraDataForCreate(): array
    {
        return [];
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize($this->getSidebarMenuPermission('create'));

        $validatedData = $request->validate($this->getValidationRules());
        $validatedData = $this->beforeStore($validatedData, $request);

        $model = $this->model::create($validatedData);

        $this->afterStore($model, $validatedData, $request);

        return redirect()
            ->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Registro criado com sucesso!');
    }

    public function show(string $id): Response
    {
        $this->authorize($this->getSidebarMenuPermission('show'));

        $modelInstance = $this->model::with($this->getWithRelations())->findOrFail($id);

        return Inertia::render($this->getViewShow(), [
            'model' => $modelInstance,
            'breadcrumbs' => $this->generateDefaultBreadcrumbs('show', $modelInstance),
            'pageTitle' => $this->generatePageTitle('show', $modelInstance),
            'pageDescription' => $this->generatePageDescription('show', $modelInstance),
            'isLandlord' => true,
        ]);
    }

    protected function getInitialValuesForCreate(Model $modelInstance, array $fields = []): array
    {
        return [];
    }

    public function edit(string $id): Response
    {
        $this->authorize($this->getSidebarMenuPermission('edit'));

        $modelInstance = $this->model::with($this->getWithRelations())->findOrFail($id);
        $fields = $this->processFields($modelInstance);

        return Inertia::render($this->getViewEdit(), [
            'model' => $modelInstance,
            'fields' => $fields,
            'breadcrumbs' => $this->generateDefaultBreadcrumbs('edit', $modelInstance),
            'pageTitle' => $this->generatePageTitle('edit', $modelInstance),
            'pageDescription' => $this->generatePageDescription('edit', $modelInstance),
            'initialValues' => $this->getInitialValuesForEdit($modelInstance, $fields),
            'isLandlord' => true,
            ...$this->getExtraDataForEdit(),
        ]);
    }

    protected function getInitialValuesForEdit(Model $modelInstance, array $fields = []): array
    {
        $initialValues = [];
        foreach ($fields as $field) {
            $fieldName = $field['name'] ?? $field['accessorKey'] ?? null;
            if ($fieldName && $modelInstance->hasAttribute($fieldName)) {
                $initialValues[$fieldName] = $modelInstance->getAttribute($fieldName);
            }
        }
        return $initialValues;
    }

    public function resolveRelationship($relationship, $modelInstance, $labelAttribute = 'name', $valueAttribute = 'id'): array
    {
        if ($modelInstance->relationLoaded($relationship)) {
            $related = $modelInstance->getRelation($relationship);
            if ($related) {
                return [
                    'label' => $related->getAttribute($labelAttribute),
                    'value' => $related->getAttribute($valueAttribute),
                ];
            }
        }
        return [];
    }

    protected function getExtraDataForEdit(): array
    {
        return [];
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorize($this->getSidebarMenuPermission('edit'));

        $modelInstance = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $modelInstance));
        $validatedData = $this->beforeUpdate($validatedData, $request, $modelInstance);

        $dataToUpdate = $this->getDataToUpdate($validatedData, $modelInstance);
        $modelInstance->update($dataToUpdate);

        $this->afterUpdate($modelInstance, $validatedData, $request);

        return redirect()
            ->route($this->getRouteNameBase() . '.index')
            ->with('success', 'Registro atualizado com sucesso!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize($this->getSidebarMenuPermission('destroy'));

        $modelInstance = $this->model::findOrFail($id);

        $this->beforeDestroy($modelInstance);

        try {
            $modelInstance->delete();
            $this->afterDestroy($modelInstance);
            
            return redirect()
                ->route($this->getRouteNameBase() . '.index')
                ->with('success', 'Registro excluído com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao excluir registro: ' . $e->getMessage());
            return redirect()
                ->route($this->getRouteNameBase() . '.index')
                ->with('error', 'Erro ao excluir o registro. Tente novamente.');
        }
    }

    protected function moveTemporaryFile(string $temporaryPath, string $targetDirectory): ?string
    {
        try {
            if (!Storage::disk('local')->exists($temporaryPath)) {
                return null;
            }

            $filename = basename($temporaryPath);
            $targetPath = $targetDirectory . '/' . $filename;

            if (Storage::disk('public')->put($targetPath, Storage::disk('local')->get($temporaryPath))) {
                Storage::disk('local')->delete($temporaryPath);
                return $targetPath;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao mover arquivo temporário: ' . $e->getMessage());
            return null;
        }
    }
} 