<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support;

use Callcocam\LaraGatekeeper\Core;
use Closure;

class ExportAction extends Action
{
    use Core\Concerns\BelongsToIcon;
    use Core\Concerns\BelongsToOptions;
    use Core\Concerns\BelongsToType;

    protected string $component = 'ExportAction';

    protected string | int | float | bool | array | null $modelValue = '';
    /**
     * Formatação da coluna
     *
     * @var Closure|string|null
     */
    protected Closure|string|null $formatUsing = null;
}
