<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table;

class RowAction
{
    protected string $name;
    protected string $label;
    protected ?string $icon = null;
    protected string $variant = 'default';
    protected ?string $route = null;
    protected ?string $href = null;
    protected ?string $shortcut = null;
    protected bool $visible = true;

    public function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
    }

    public static function make(string $name, string $label): static
    {
        return new static($name, $label);
    }

    public function getName(): string { return $this->name; }
    public function getLabel(): string { return $this->label; }
    public function getIcon(): ?string { return $this->icon; }
    public function getVariant(): string { return $this->variant; }
    public function getRoute(): ?string { return $this->route; }
    public function getHref(): ?string { return $this->href; }
    public function getShortcut(): ?string { return $this->shortcut; }
    public function getVisible(): bool { return $this->visible; }

    public function icon(string $icon): static { $this->icon = $icon; return $this; }
    public function variant(string $variant): static { $this->variant = $variant; return $this; }
    public function route(string $route): static { $this->route = $route; return $this; }
    public function href(string $href): static { $this->href = $href; return $this; }
    public function shortcut(string $shortcut): static { $this->shortcut = $shortcut; return $this; }
    public function visible(bool $visible): static { $this->visible = $visible; return $this; }
} 