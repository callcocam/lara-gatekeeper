<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper;

use Illuminate\Routing\ResourceRegistrar;

class ExtendedResourceRegistrar extends ResourceRegistrar
{
    /**
     * Route a resource to a controller with configurable extended functionalities
     */
    public function register($name, $controller, array $options = [])
    {
        // Registrar as rotas básicas do resource
        parent::register($name, $controller, $options);
        
        // Verificar quais extensões estão habilitadas
        $extensions = $options['extensions'] ?? ['bulk', 'import', 'export'];
        
        foreach ($extensions as $extension) {
            $method = 'add' . ucfirst($extension) . 'Routes';
            if (method_exists($this, $method)) {
                $this->$method($name, $controller, $options);
            }
        }
    }

    /**
     * Add bulk action routes for the resource
     */
    protected function addBulkRoutes($name, $controller, array $options = [])
    {
        $bulkRoutes = $options['bulk_routes'] ?? [
            'destroy' => ['method' => 'delete', 'action' => 'bulkDestroy'],
            'update' => ['method' => 'patch', 'action' => 'bulkUpdate'],
            'action' => ['method' => 'post', 'action' => 'bulkAction', 'path' => 'bulk-action']
        ];

        foreach ($bulkRoutes as $route => $config) {
            $path = $config['path'] ?? "bulk/{$route}";
            $method = $config['method'];
            $action = $config['action'];

            $this->router->$method("{$name}/{$path}", [
                'uses' => "{$controller}@{$action}",
                'as' => "{$name}.bulk.{$route}"
            ]);
        }
    }

    /**
     * Add import routes for the resource
     */
    protected function addImportRoutes($name, $controller, array $options = [])
    {
        $importRoutes = $options['import_routes'] ?? [
            'form' => ['method' => 'get', 'action' => 'import', 'path' => 'import'],
            'process' => ['method' => 'post', 'action' => 'processImport', 'path' => 'import'],
            'template' => ['method' => 'get', 'action' => 'importTemplate', 'path' => 'import/template']
        ];

        foreach ($importRoutes as $route => $config) {
            $path = $config['path'];
            $method = $config['method'];
            $action = $config['action'];

            $this->router->$method("{$name}/{$path}", [
                'uses' => "{$controller}@{$action}",
                'as' => "{$name}.import.{$route}"
            ]);
        }
    }

    /**
     * Add export routes for the resource
     */
    protected function addExportRoutes($name, $controller, array $options = [])
    {
        $exportRoutes = $options['export_routes'] ?? [
            'all' => ['method' => 'get', 'action' => 'export', 'path' => 'export'],
            'selected' => ['method' => 'post', 'action' => 'exportSelected', 'path' => 'export'],
            'filtered' => ['method' => 'post', 'action' => 'exportFiltered', 'path' => 'export/filtered']
        ];

        foreach ($exportRoutes as $route => $config) {
            $path = $config['path'];
            $method = $config['method'];
            $action = $config['action'];

            $this->router->$method("{$name}/{$path}", [
                'uses' => "{$controller}@{$action}",
                'as' => "{$name}.export.{$route}"
            ]);
        }
    }

    /**
     * Add custom routes for the resource
     */
    protected function addCustomRoutes($name, $controller, array $options = [])
    {
        $customRoutes = $options['custom_routes'] ?? [];

        foreach ($customRoutes as $route => $config) {
            $path = $config['path'] ?? $route;
            $method = $config['method'] ?? 'get';
            $action = $config['action'] ?? $route;

            $this->router->$method("{$name}/{$path}", [
                'uses' => "{$controller}@{$action}",
                'as' => "{$name}.{$route}"
            ]);
        }
    }
}
