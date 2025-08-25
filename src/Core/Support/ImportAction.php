<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support;

use Callcocam\LaraGatekeeper\Core;
use Closure;

class ImportAction extends Action
{
    use Core\Concerns\FactoryPattern;
    use Core\Concerns\BelongsToOptions;
    use Core\Concerns\BelongsToType;
    use Core\Concerns\BelongsToModal;

    protected string $component = 'ImportAction';
    /**
     * Formatação da coluna
     *
     * @var Closure|string|null
     */
    protected Closure|string|null $formatUsing = null;

    public function toArray($item = null): array
    {
        return array_merge(parent::toArray($item), [
            'type' => $this->getType(),
            'modal' => $this->getModal(),            
        ]);
    }
}
