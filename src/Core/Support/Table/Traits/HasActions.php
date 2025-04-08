<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Traits;

use Callcocam\LaraGatekeeper\Core\Support\Table\Actions\Action;

trait HasActions
{
    protected array $actions = [];

    public function addAction(Action $action): static
    {
        $this->actions[] = $action;
        return $this;
    }

    public function actions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->addAction($action);
        }
        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }
}
