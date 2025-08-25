<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper;

use Illuminate\Routing\ResourceRegistrar;

class BulkResourceRegistrar extends ResourceRegistrar
{
    /**
     * Route a resource to a controller with bulk action support
     */
    public function register($name, $controller, array $options = [])
    {
        parent::register($name, $controller, $options);

        // Adiciona rota de bulk action automaticamente
        $this->router->post("{$name}/bulk-action", $controller . '@bulkAction')
            ->name("{$name}.bulk-action");
    }
}
