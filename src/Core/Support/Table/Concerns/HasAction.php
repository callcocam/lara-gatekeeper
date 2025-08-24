<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Concerns;

use Callcocam\LaraGatekeeper\Core\Support\Action;

trait HasAction
{
    protected array $actions = [];

    public function action(array $actions): static
    {
        $this->actions[] = Action::make($actions['label'] ?? 'Action')
            ->id($actions['id'] ?? 'action')
            ->icon($actions['icon'] ?? null)
            ->color($actions['color'] ?? null)
            ->accessorKey($actions['accessorKey'] ?? null)
            ->cellCallback($actions['cellCallback'] ?? null)
            ->isHtml($actions['isHtml'] ?? false);
        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }
}
