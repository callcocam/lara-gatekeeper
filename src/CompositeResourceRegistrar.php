<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper;

use Illuminate\Routing\ResourceRegistrar;

class CompositeResourceRegistrar extends ResourceRegistrar
{
    /**
     * Route a resource to a controller with all extended functionalities
     */
    public function register($name, $controller, array $options = [])
    {
        // Registrar as rotas básicas do resource
        parent::register($name, $controller, $options);

        // Adicionar funcionalidades de bulk actions
        $this->addBulkRoutes($name, $controller, $options);

        // Adicionar funcionalidades de import
        $this->addImportRoutes($name, $controller, $options);

        // Adicionar funcionalidades de export
        $this->addExportRoutes($name, $controller, $options);
    }

    /**
     * Add bulk action routes for the resource
     */
    protected function addBulkRoutes($name, $controller, array $options = [])
    {
        if ($this->shouldAddRoute('bulk', $options)) {
            // Bulk delete
            $this->router->delete("{$name}/bulk", [
                'uses' => "{$controller}@bulkDestroy",
                'as' => "{$name}.bulk.destroy"
            ]);

            // Bulk update
            $this->router->patch("{$name}/bulk", [
                'uses' => "{$controller}@bulkUpdate",
                'as' => "{$name}.bulk.update"
            ]);

            // Bulk action genérica
            $this->router->post("{$name}/bulk-action", [
                'uses' => "{$controller}@bulkAction",
                'as' => "{$name}.bulk.action"
            ]);
        }
    }

    /**
     * Add import routes for the resource
     */
    protected function addImportRoutes($name, $controller, array $options = [])
    {
        if ($this->shouldAddRoute('import', $options)) {
            // Mostrar formulário de import 
            // Processar import
            $this->router->post("{$name}/import", [
                'uses' => "{$controller}@import",
                'as' => "{$name}.import"
            ]);
        }
    }

    /**
     * Add export routes for the resource
     */
    protected function addExportRoutes($name, $controller, array $options = [])
    {
        if ($this->shouldAddRoute('export', $options)) {
            // Export todos os registros

            // Export registros selecionados
            $this->router->post("{$name}/export", [
                'uses' => "{$controller}@export",
                'as' => "{$name}.export"
            ]);

            // Export com filtros
            // $this->router->post("{$name}/export/filtered", [
            //     'uses' => "{$controller}@exportFiltered",
            //     'as' => "{$name}.export.filtered"
            // ]);
        }
    }

    /**
     * Determine if we should add the specific route type
     */
    protected function shouldAddRoute($type, array $options = [])
    {
        $except = $options['except'] ?? [];
        $only = $options['only'] ?? [];

        // Se only está definido e não inclui este tipo, não adicionar
        if (!empty($only) && !in_array($type, $only)) {
            return false;
        }

        // Se except inclui este tipo, não adicionar
        if (in_array($type, $except)) {
            return false;
        }

        return true;
    }
}
