<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Traits;

use Callcocam\LaraGatekeeper\Core\Support\Table\RowAction;
use Closure;

trait HasRowActions
{
    protected array $rowActions = [];

    public function rowAction(RowAction $action): RowAction
    {

        $this->rowActions[] = $action;

        return $action;
    }


    public function rowActions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->rowAction($action);
        }

        return $this;
    }

    public function getRowActions(): array
    {
        return collect($this->rowActions)
            ->filter(fn(RowAction $action): bool => $this->evaluate($action->getVisible()))
            ->map(function (RowAction $action): array {
                return [
                    'label' => $this->evaluate($action->getLabel()),
                    'action' => $action->getName(),
                    'route' => $action->getRoute(),
                    'href' =>  $action->getHref(),
                    'icon' => $action->getIcon(),
                    'variant' => $action->getVariant(),
                    'shortcut' => $action->getShortcut(),
                ];
            })
            ->toArray();
    }
}
