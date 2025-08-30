<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Actions;
 
use Callcocam\LaraGatekeeper\Core\Support\Action as SupportAction;

class SubmitAction extends SupportAction
{
    protected string $component = 'SubmitAction';

    public function __construct(string $name, ?string $header = null)
    {
        parent::__construct($name, $header);
        $this->component('SubmitAction')
        ->label('Salvar ou atualizar')
        ->order(1)
        ->position('footer')
        ->icon('Check')
        ->hiddenWhenIndex()
        ->hiddenWhenShow()
        ->variant('primary')
        ->type('submit') 
        ->requiresPermissions(['create-records', 'update-records']);
       
    }
}