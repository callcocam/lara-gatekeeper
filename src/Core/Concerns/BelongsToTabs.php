<?php

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Callcocam\LaraGatekeeper\Core\Support\Form\Fields\Tab;

trait BelongsToTabs
{
    protected ?array $tabs = [];

    public function tabs(array $tabs): static
    {
        foreach ($tabs as $order => $tab) {
            $this->addTab($tab, $order);
        }
        return $this;
    }

    public function getTab(string $name): ?Tab
    {
        return collect($this->tabs)->firstWhere('name', $name);
    }

    public function addTab(Tab $tab, $order = 0): static
    {
        $this->tabs[] = $tab->order($order);
        return $this;
    }

    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function hasTabs(): bool
    {
        return !empty($this->tabs);
    }

    public function getTabsForForm(): array
    {
        return array_map(fn(Tab $tab) => $tab->toArray(), $this->tabs);
    }
}
