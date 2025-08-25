<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Callcocam\LaraGatekeeper\Core\Cast\EloquentCastIntegration;
use Callcocam\LaraGatekeeper\Core\Cast\ModelIntrospection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait IteractWithTable
{
    use HasApplyFormatter;
    protected Request $request;
    protected $modelIntrospection = null;
    protected array $searchableColumns = [];
    protected array $relationshipColumns = [];
    /**
     * Detecta configuração do modelo automaticamente
     */
    public function detectModelConfiguration(): void
    {
        if (!$this->model) {
            return;
        }

        $modelClass = is_string($this->model) ? $this->model : get_class($this->model);

        try {
            // Introspecção simplificada
            $this->modelIntrospection = ModelIntrospection::analyze($modelClass);

            // Auto-detecção opcional
            EloquentCastIntegration::detectAndRegister($modelClass);

            // Configura colunas pesquisáveis
            $this->configureSearchableColumns();
        } catch (\Exception $e) {
            // Ignora erros de detecção silenciosamente
            throw $e;
        }
    }

    public function toArray(): array
    {
        if (method_exists($this, 'detectModelConfiguration')) {
            $this->detectModelConfiguration();
        }

        // Processa os dados
        $result = $this->processTableData();

        // Adiciona configurações da tabela
        $result = array_merge($result, [
            'filters' => $this->getQueryParams(),
        ]);
        Storage::put('raptor.json', json_encode([
            'table' => $result,
        ])); // Armazena para debug

        return $result;
    }

    /**
     * Processa os dados da tabela baseado no tipo
     */
    protected function processTableData(): array
    {

        $paginator = $this->query->paginate($this->getPerPage())
            ->onEachSide(2)
            ->withQueryString();


        return [
            'data' => [
                'data' => array_map(function ($item) {
                    return $this->applyAutoFormatting($item);
                }, $paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'next' => $paginator->nextPageUrl(),
                    'prev' => $paginator->previousPageUrl(),
                    'links' => $paginator->linkCollection()->toArray(),
                    'path' => $paginator->path(),
                    'select_per_page' => $this->selectPerPageOptions()
                ],
            ]
        ];
    }

    /**
     * Executa bulk action via table
     */
    public function handleTableBulkAction(string $actionName, array $selectedIds, array $data = []): array
    {
        return $this->executeBulkAction($actionName, $selectedIds, $data);
    }


    /**
     * Obtém os parâmetros de query
     */
    protected function getQueryParams(): array
    {
        return $this->getRequest()->only(array_merge(
            ['search', 'sort', 'direction', 'per_page']
        ));
    }

    protected function getPerPage(): int
    {
        $params = $this->getQueryParams();
        return isset($params['per_page']) && is_numeric($params['per_page']) ? (int)$params['per_page'] : 15;
    }

    protected function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    protected function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Configura colunas pesquisáveis baseado nas colunas da tabela
     */
    protected function configureSearchableColumns(): void
    {
        foreach ($this->columns() as $column) {
            if ($column->isSearchable()) {
                $this->searchableColumns[] = $column->getName();
            }
        }
    }

    protected function selectPerPageOptions(): array
    {
        return [5, 10, 25, 50, 100];
    }
}