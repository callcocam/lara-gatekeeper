<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Actions;

use Callcocam\LaraGatekeeper\Core\Support\Action;

class LinkAction extends Action
{
    protected string $component = 'Link';
    public ?string $position = 'top';
}
