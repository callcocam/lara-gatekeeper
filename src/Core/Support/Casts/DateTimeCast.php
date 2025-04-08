<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

 namespace Callcocam\LaraGatekeeper\Core\Support\Casts;

class DateTimeCast extends DateCast
{
    public function __construct()
    {
        parent::__construct('Y-m-d H:i:s');
    }
}
