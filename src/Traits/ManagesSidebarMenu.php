<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits;

use Illuminate\Support\Str;

trait ManagesSidebarMenu
{
    /**
     * Nome do ícone Lucide para este item de menu.
     * Pode ser sobrescrito pela propriedade $sidebarIcon no controller.
     */
    protected string $sidebarIcon = 'Folder'; // Ícone padrão

    /**
     * Determina se este recurso deve aparecer no menu lateral.
     *
     * @return bool
     */
    public function showInSidebar(): bool
    {
        return true;
    }

    /**
     * Retorna o título a ser exibido no menu lateral.
     *
     * @return string
     */
    public function getSidebarMenuTitle(): string
    {
        // Assume que $this->pluralResourceName está definido no controller que usa o Trait
        return Str::ucfirst(str_replace('_', ' ', $this->pluralResourceName ?? 'Recurso'));
    }

    /**
     * Retorna o nome do ícone Lucide para o menu lateral.
     *
     * @return string
     */
    public function getSidebarMenuIconName(): string
    {
        // Usa a propriedade $sidebarIcon se definida, senão o padrão do trait.
        return property_exists($this, 'sidebarIcon') ? $this->sidebarIcon : 'Folder';
    }

    /**
     * Retorna a ordem de exibição no menu lateral (menor aparece primeiro).
     *
     * @return int
     */
    public function getSidebarMenuOrder(): int
    {
        return 100; // Padrão alto para aparecer depois dos itens fixos
    }

    /**
     * Retorna a definição completa do item de menu para este controller.
     * Retorna null se não deve ser exibido.
     *
     * @return array|null
     */
    public function getSidebarMenuItem(): ?array
    {
        if (!$this->showInSidebar()) {
            return null;
        }

        // Assume que $this->getRouteNameBase() está disponível no controller
        $routeName = $this->getRouteNameBase() . '.index';

        // Verifica se a rota realmente existe
        if (!\Illuminate\Support\Facades\Route::has($routeName)) {
            // Logar um aviso ou simplesmente não adicionar ao menu
             \Illuminate\Support\Facades\Log::warning("Sidebar Menu: Rota '{$routeName}' não encontrada para controller " . get_class($this));
            return null;
        }

        return [
            'title' => $this->getSidebarMenuTitle(),
            'href' => route($routeName),
            'iconName' => $this->getSidebarMenuIconName(),
            'order' => $this->getSidebarMenuOrder(),
        ];
    }

    public function getSidebarMenuPermission($action = 'index'): string
    {
        $routeName = $this->getRouteNameBase() . '.' . $action;
        return $routeName;
    }
} 