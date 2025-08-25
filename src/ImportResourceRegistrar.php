<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper;

use Illuminate\Routing\ResourceRegistrar;

class ImportResourceRegistrar extends ResourceRegistrar
{
    /**
     * Route a resource to a controller with bulk action support
     */
    public function register($name, $controller, array $options = [])
    {
        parent::register($name, $controller, $options);
        dd($name);

        // Adiciona rota de importação automaticamente
        $this->router->post("{$name}/import", [$controller, 'import'])
            ->name("{$name}.import");
    }
}
