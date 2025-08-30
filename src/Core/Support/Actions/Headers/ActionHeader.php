<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Core\Support\Actions\Headers;

use Callcocam\LaraGatekeeper\Core\Support\Action;
use Callcocam\LaraGatekeeper\Core\Support\Actions\Headers\HeaderInterface;

class ActionHeader extends Action implements HeaderInterface
{
    protected string $position = 'header';
}
