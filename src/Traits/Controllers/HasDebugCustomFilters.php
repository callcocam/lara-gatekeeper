<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

trait HasDebugCustomFilters
{
    protected function getToArrayHasDebugCustomFilters(): array
    {
        return [
            'debugCustomFilters' => [
                'method_exists' => method_exists($this, 'useCustomFilters'),
                'class' => get_class($this),
                'result' => method_exists($this, 'useCustomFilters') ? $this->useCustomFilters() : false,
                'method_result_direct' => method_exists($this, 'useCustomFilters') ? call_user_func([$this, 'useCustomFilters']) : 'method_not_exists',
                'reflection_check' => (new \ReflectionClass($this))->hasMethod('useCustomFilters'),
            ],
        ];
    }
}
