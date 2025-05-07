<?php

namespace Callcocam\LaraGatekeeper\Core\Support;

use Closure;

class Action
{
    use Concerns\HasIcon;
    use Concerns\HasColor;
    use Concerns\HasLabel;
    use Table\Concerns\HasAction;

    // 'permission' => $action['permission'] ?? null,
    // 'variant' => $action['variant'] ?? 'outline',
    // 'isLink' => $action['isLink'] ?? false,
    // 'routeSuffix' => $action['routeSuffix'] ?? null,
    // 'title' => $action['title'] ?? '',
    // 'icon' => $action['icon'] ?? null,
    // // 'row' => $action['row'] ?? null,
    // 'routeNameBase' => $this->getRouteNameBase(),
    public string $id;
    public string $accessorKey;
    public ?string $variant = null; // Variantes de cores
    public ?string $routeSuffix = null; // Sufixo da rota
    public ?string $routeNameBase = null; // Nome base da rota
    public ?string $permission = null; // Permissão necessária para o botão
    public ?string $header = null;
    public ?Closure $cellCallback = null;
    public bool $isHtml = false; // Para indicar se a célula retorna HTML bruto
    public bool $isLink = true; // Para indicar se o botão é um link ou não
    public ?string $fullRouteName = null; // Nome completo da rota

    protected function __construct(string $accessorKey, ?string $header = null)
    {
        $this->header = $header ?? str($accessorKey)->title()->toString();
        $this->accessorKey = $accessorKey;
        $this->id = $this->accessorKey; // ID padrão baseado na chave de acesso
    }
    public static function make(string $accessorKey, ?string $header = null): self
    {
        return new static($accessorKey, $header);
    }
    public function id(string $id): self
    {
        $this->id = $id;
        // Se accessorKey não foi definido explicitamente e id não é 'actions', usar id como accessorKey
        if ($this->accessorKey === strtolower(str_replace(' ', '_', $this->header)) && $id !== 'actions') {
            $this->accessorKey = $id;
        }
        return $this;
    }

    public function header(string $header): self
    {
        $this->header = $header;
        return $this;
    }

    public function accessorKey(?string $key): self
    {
        $this->accessorKey = $key;
        return $this;
    }

    public function variant(?string $variant): self
    {
        $this->variant = $variant;
        return $this;
    }
    public function routeSuffix(?string $routeSuffix): self
    {
        $this->routeSuffix = $routeSuffix;
        return $this;
    }
    public function routeNameBase(?string $routeNameBase): self
    {
        $this->routeNameBase = $routeNameBase;
        return $this;
    }
    public function permission(?string $permission): self
    {
        $this->permission = $permission;
        return $this;
    }
    public function cellCallback(?Closure $callback): self
    {
        $this->cellCallback = $callback;
        return $this;
    }
    public function setIsHtml(bool $isHtml = true): self
    {
        $this->isHtml = $isHtml;
        return $this;
    }
    public function isLink(bool $isLink = true): self
    {
        $this->isLink = $isLink;
        return $this;
    }
    public function fullRouteName(string $fullRouteName): self
    {
        $this->fullRouteName = $fullRouteName;
        return $this;
    }
    public function getIsLink(): bool
    {
        return $this->isLink;
    }
    public function getRouteNameBase(): ?string
    {
        return $this->routeNameBase;
    }
    public function getRouteSuffix(): ?string
    {
        return $this->routeSuffix;
    }
    public function getPermission(): ?string
    {
        return $this->permission;
    }
    public function getVariant(): ?string
    {
        return $this->variant;
    }
    public function getId(): string
    {
        return $this->id;
    }
    public function getAccessorKey(): string
    {
        return $this->accessorKey;
    }
    public function getHeader(): string
    {
        return $this->header;
    }
    public function getCellCallback(): ?Closure
    {
        return $this->cellCallback;
    }
    public function isHtml(): bool
    {
        return $this->isHtml;
    }
    public function getFullRouteName(): ?string
    {
        return $this->fullRouteName;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'accessorKey' => $this->getAccessorKey(),
            'header' => $this->getHeader(),
            'variant' => $this->getVariant(),
            'routeSuffix' => $this->getRouteSuffix(),
            'routeNameBase' => $this->getRouteNameBase(),
            'permission' => $this->getPermission(),
            'cellCallback' => $this->getCellCallback(),
            'isHtml' => $this->isHtml(),
            'isLink' => $this->getIsLink(),
            'icon' => $this->getIcon(),
            'fullRouteName' => $this->getFullRouteName(),
        ];
    }
}
