<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Callcocam\LaraGatekeeper\LaraGatekeeper
 */
class LaraGatekeeper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Callcocam\LaraGatekeeper\LaraGatekeeper::class;
    }
}
