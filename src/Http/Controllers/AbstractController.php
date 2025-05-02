<?php

/**
 * @package Callcocam\LaraGatekeeper\Http\Controllers
 */

namespace Callcocam\LaraGatekeeper\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\Inertia;
use Callcocam\LaraGatekeeper\Traits\ManagesSidebarMenu;

abstract class AbstractController extends Controller
{
    use ManagesSidebarMenu;

    protected ?string $model = null;
    protected string $resourceName = '';
    protected string $pluralResourceName = '';
    protected string $routeNameBase = '';
    protected string $viewPrefix = 'Crud';

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
            $this->resourceName = Str::snake($baseName);
            $this->pluralResourceName = Str::plural($this->resourceName);
            $this->routeNameBase = 'admin.' . $this->pluralResourceName;
        } else {
            throw new \Exception('A propriedade $model deve ser definida no controller filho.');
        }
    }

    protected function getResourceName(): string
    {
        return $this->resourceName;
    }

    protected function getPluralResourceName(): string
    {
        return $this->pluralResourceName;
    }

    protected function getRouteNameBase(): string
    {
        return $this->routeNameBase;
    }

    protected function generatePageTitle(string $action, ?Model $modelInstance = null): string
    {
        $resourceTitle = Str::ucfirst(str_replace('_', ' ', $this->getPluralResourceName()));
        switch ($action) {
            case 'index':
                return "Gerenciar {$resourceTitle}";
            case 'create':
                return "Cadastrar Novo " . Str::singular($resourceTitle);
            case 'edit':
                $identifier = $modelInstance ? ($modelInstance->name ?? $modelInstance->id) : '';
                return "Editar " . Str::singular($resourceTitle) . ($identifier ? ": {$identifier}" : '');
            case 'show':
                $identifier = $modelInstance ? ($modelInstance->name ?? $modelInstance->id) : '';
                return "Detalhes de " . Str::singular($resourceTitle) . ($identifier ? ": {$identifier}" : '');
            default:
                return $resourceTitle;
        }
    }

    protected function generatePageDescription(string $action, ?Model $modelInstance = null): string
    {
        return '';
    }

    protected function generateDefaultBreadcrumbs(string $action, ?Model $modelInstance = null): array
    {
        $pluralTitle = Str::ucfirst(str_replace('_', ' ', $this->getPluralResourceName()));
        $singularTitle = Str::singular($pluralTitle);
        $indexRoute = route($this->getRouteNameBase() . '.index');

        $breadcrumbs = [
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
        return ['auth'];
    }

    abstract protected function getFields(?Model $model = null): array;
    abstract protected function getTableColumns(): array;
    abstract protected function getFilters(): array;
    abstract protected function getValidationRules(bool $isUpdate = false, ?Model $model = null): array;
    abstract protected function getSearchableColumns(): array;

    /**
     * Define os relacionamentos que devem ser carregados (eager loaded)
     * na listagem principal (método index).
     *
     * @return array
     */
    protected function getWithRelations(): array
    {
        return []; // Padrão: não carregar nenhum relacionamento
    }

    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 15);
        
        // Iniciar query e carregar relacionamentos definidos
        $query = $this->model::query();
        $relationsToLoad = $this->getWithRelations();
        if (!empty($relationsToLoad)) {
            $query->with($relationsToLoad);
        }

        $tableColumns = $this->getTableColumns();

        // Aplicar filtros (lógica básica, pode precisar de mais refinamento)
        foreach ($this->getFilters() as $filter) {
            if ($request->filled($filter['column'])) {
                // Assumindo filtro simples por 'where' por enquanto
                $query->where($filter['column'], $request->input($filter['column']));
            }
        }

        // Aplicar busca (usando getSearchableColumns)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $searchableDbColumns = $this->getSearchableColumns(); // Obter colunas do novo método

            if (!empty($searchableDbColumns)) {
                $query->where(function($q) use ($search, $searchableDbColumns) {
                    foreach ($searchableDbColumns as $dbColumn) {
                         // Assume que getSearchableColumns retorna nomes de coluna válidos do DB
                         // Adicionar tratamento para relacionamentos se necessário (ex: 'relation.field')
                        if (str_contains($dbColumn, '.')) {
                             // Exemplo básico para relacionamento (pode precisar de joins)
                            [$relation, $relatedColumn] = explode('.', $dbColumn, 2);
                             $q->orWhereHas($relation, function($relationQuery) use ($relatedColumn, $search) {
                                 $relationQuery->where($relatedColumn, 'like', "%{$search}%");
                             });
                         } else {
                            $q->orWhere($dbColumn, 'like', "%{$search}%");
                         }
                    }
                });
            }
        }

        // Aplicar ordenação (lógica aprimorada)
        if ($request->has('sort')) {
            $direction = $request->input('direction', 'asc');
            $columnKey = $request->input('sort'); // A chave da coluna (accessorKey ou id)

            // Encontrar a definição da coluna solicitada
            $sortColumnDef = null;
            foreach ($tableColumns as $colDef) {
                if (($colDef['accessorKey'] ?? $colDef['id'] ?? null) === $columnKey) {
                    $sortColumnDef = $colDef;
                    break;
                }
            }

            // Verificar se a coluna existe e está marcada como sortable
            if ($sortColumnDef && ($sortColumnDef['sortable'] ?? false)) {
                // Usar accessorKey ou id como nome da coluna no DB
                 $dbColumn = $sortColumnDef['accessorKey'] ?? $sortColumnDef['id'] ?? null;
                 if ($dbColumn) {
                     $query->orderBy($dbColumn, $direction);
                 }
            }
        } else {
             // Tentar ordenar pela primeira coluna sortable ou created_at como padrão
             $defaultSortColumn = null;
             foreach($tableColumns as $colDef) {
                 if ($colDef['sortable'] ?? false) {
                     $defaultSortColumn = $colDef['accessorKey'] ?? $colDef['id'] ?? null;
                     if ($defaultSortColumn) break;
                 }
             }
             if ($defaultSortColumn) {
                 $query->orderBy($defaultSortColumn, 'asc');
             } else {
                  $query->latest(); // Fallback para latest()
             }
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return Inertia::render("{$this->viewPrefix}/Index", [
            'data' => [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                ],
                'links' => [
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
            'columns' => $tableColumns, // Passar as definições completas
            'filters' => $request->only(array_merge(['search', 'sort', 'direction', 'per_page'], array_column($this->getFilters(), 'key'))),
            'filterOptions' => $this->getFilters(),
            'pageTitle' => $this->generatePageTitle('index'),
            'pageDescription' => $this->generatePageDescription('index'),
            'breadcrumbs' => $this->generateDefaultBreadcrumbs('index'),
            'routeNameBase' => $this->getRouteNameBase(),
            // Adicionar permissões (can) se necessário
        ]);
    }

    public function create(): Response
    {
        return Inertia::render("{$this->viewPrefix}/Create", [
            'fields' => $this->getFields(),
            'initialValues' => new $this->model(), // Objeto vazio para o form
            'pageTitle' => $this->generatePageTitle('create'),
            'pageDescription' => $this->generatePageDescription('create'),
            'breadcrumbs' => $this->generateDefaultBreadcrumbs('create'),
            'routeNameBase' => $this->getRouteNameBase(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate($this->getValidationRules());

        // Lógica para tratar senha, se existir
        if (isset($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        }

        $this->model::create($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
            ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' criado(a) com sucesso.');
    }

    public function show(string $id): Response
    {
        $modelInstance = $this->model::findOrFail($id);
        return Inertia::render("{$this->viewPrefix}/Show", [
            'model' => $modelInstance,
            'pageTitle' => $this->generatePageTitle('show', $modelInstance),
            'pageDescription' => $this->generatePageDescription('show', $modelInstance),
            'breadcrumbs' => $this->generateDefaultBreadcrumbs('show', $modelInstance),
            'routeNameBase' => $this->getRouteNameBase(),
        ]);
    }

    public function edit(string $id): Response
    {
        $modelInstance = $this->model::findOrFail($id);
        return Inertia::render("{$this->viewPrefix}/Edit", [
            'fields' => $this->getFields($modelInstance),
            'initialValues' => $modelInstance,
            'modelId' => $id, // Passar o ID para a action do form
            'pageTitle' => $this->generatePageTitle('edit', $modelInstance),
            'pageDescription' => $this->generatePageDescription('edit', $modelInstance),
            'breadcrumbs' => $this->generateDefaultBreadcrumbs('edit', $modelInstance),
            'routeNameBase' => $this->getRouteNameBase(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $modelInstance = $this->model::findOrFail($id);
        $validatedData = $request->validate($this->getValidationRules(true, $modelInstance));

        // Lógica para tratar senha, se existir e não estiver vazia
        if (isset($validatedData['password']) && !empty($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']); // Remover senha se vazia para não sobrescrever
        }

        $modelInstance->update($validatedData);

        return redirect()->route($this->getRouteNameBase() . '.index')
             ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' atualizado(a) com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $modelInstance = $this->model::findOrFail($id);
        // Adicionar verificação de permissão aqui (Gate::authorize)
        $modelInstance->delete();

        return redirect()->route($this->getRouteNameBase() . '.index')
             ->with('success', Str::ucfirst(Str::singular($this->getResourceName())) . ' excluído(a) com sucesso.');
    }
}
