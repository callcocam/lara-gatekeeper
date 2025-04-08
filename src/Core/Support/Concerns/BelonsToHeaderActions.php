<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br 
 * 
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

 use Callcocam\LaraGatekeeper\Core\Support\Actions\HeaderAction;

trait BelonsToHeaderActions
{
    /** @var HeaderAction[] */
    protected array $headerActions = [];

    /**
     * @param HeaderAction $action
     * @return $this
     */
    public function headerAction(HeaderAction $action): static
    {
        if (!$action->isVisible()) {
            return $this;
        }
        $this->headerActions[] = $action;
        return $this;
    }
    /**
     * @param HeaderAction[] $actions
     * @return $this
     */
    public function headerActions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->headerAction($action);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaderActions(): array
    { 
        return collect($this->headerActions)
            ->filter(fn(HeaderAction $action) => $action->isVisible())
            ->map(fn(HeaderAction $action) => $action->toArray())
            ->values()
            ->toArray();
    }
}
