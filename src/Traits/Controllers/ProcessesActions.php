<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Callcocam\LaraGatekeeper\Core\Support\Action;
use Callcocam\LaraGatekeeper\Core\Support\Actions\CancelAction;
use Callcocam\LaraGatekeeper\Core\Support\Actions\SubmitAction;

/**
 * Trait responsável pelo processamento de actions
 * 
 * Gerencia:
 * - Actions padrão (create, edit, delete, show)
 * - Actions de footer (cancel, save)
 * - Actions customizadas
 * - Visibilidade baseada em contexto
 * - Integração com sistema de permissões
 */
trait ProcessesActions
{
    protected array $actions = [];
    protected ?string $context = null;

    /**
     * Define o contexto atual da ação
     */
    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Obtém o contexto atual baseado na rota
     */
    public function getContext(): string
    {
        if (!$this->context) {
            $this->context = str(request()->route()->getName())->explode('.')->last();
        }
        return $this->context;
    }

    /**
     * Obtém todas as actions
     */
    protected function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Busca uma action específica pelo nome
     */
    public function getAction(string $name): ?Action
    {
        $action = collect($this->actions)->firstWhere('name', $name);
        return $action;
    }

    /**
     * Define as actions padrão para tabelas
     * Pode ser sobrescrito por controllers filhos
     */
    protected function getDefaultTableActions(): array
    {
        $actions = [];

        // Action de criar
        $actions[] = Action::make('create')
            ->order(1)
            ->position('top')
            ->icon('Plus')
            ->hiddenWhenDelete()
            ->hiddenWhenEdit()
            ->hiddenWhenShow()
            ->color('primary')
            ->routeNameBase($this->getRouteNameBase())
            ->routeSuffix('create')
            ->label('Criar')
            ->autoPolicy('create'); // Usa descoberta automática de policy

        return $actions;
    }

    /**
     * Define as actions de footer (cancelar, salvar, etc.)
     */
    protected function getFooterActions(): array
    {
        return [
            CancelAction::make('cancel')
                ->url(fn() => route($this->getRouteNameBase() . '.index')),
            SubmitAction::make('save'),
        ];
    }

    /**
     * Define actions específicas para CRUD
     */
    protected function getCrudActions(): array
    {
        $actions = [];

        // Action de visualizar
        $actions[] = Action::view($this->getRouteNameBase())
            ->order(10)
            ->position('top')
            ->icon('Eye')
            ->variant('outline')
            ->hiddenWhenCreate()
            ->hiddenWhenIndex()
            ->hiddenWhenShow()
            ->url(fn($record) => route($this->getRouteNameBase() . '.show', $record))
            ->label('Visualizar')
            ->autoPolicy('view');

        // Action de editar
        $actions[] = Action::edit($this->getRouteNameBase())
            ->order(20)
            ->position('top')
            ->icon('Edit')
            ->hiddenWhenCreate()
            ->hiddenWhenEdit()
            ->url(fn($record) => route($this->getRouteNameBase() . '.edit', $record))
            ->label('Editar')
            ->autoPolicy('update')
            ->requiresPermissions('edit-records');

        // Action de deletar
        $actions[] = Action::delete($this->getRouteNameBase())
            ->order(30)
            ->position('top')
            ->hiddenWhenCreate()
            ->label('Deletar')
            ->autoPolicy('delete')
            ->requiresPermissions('delete-records');

        return $actions;
    }

    /**
     * Define actions customizadas específicas do modelo
     * Pode ser sobrescrito por controllers filhos
     */
    protected function getCustomActions(): array
    {
        return [];
    }

    /**
     * Retorna todas as actions processadas e filtradas
     */
    protected function getTableActions(): array
    {
        // Combina todas as actions
        $actions = array_merge(
            $this->getImportOptions(),
            $this->getExportOptions(),
            $this->getDefaultTableActions(),
            $this->getCustomActions(),
        );

        $this->actions = $actions;

        // Filtra actions baseado no contexto e visibilidade
        return $this->getProcessedActions($actions);
    }

    protected function getFormActions($model = null): array
    {
        // Combina todas as actions
        $actions = array_merge(
            $this->getCustomActions(),
            $this->getCrudActions(),
            $this->getFooterActions()
        );

        $this->actions = $actions;

        // Filtra actions baseado no contexto e visibilidade
        return $this->getProcessedActions($actions, $model);
    }

    protected function getProcessedActions($actions, $model = null): array
    {
        return collect($actions)->filter(function (Action $action) use ($model) {
            return $this->isActionVisibleInContext($action);
        })->sortBy(fn(Action $action) => $action->getOrder())
            ->map(function (Action $action) use ($model) {
                return $action->render($model);
            })->toArray();
    }
    /**
     * Verifica se action é visível no contexto atual
     */
    protected function isActionVisibleInContext(Action $action): bool
    {
        $context = $this->getContext();

        $isVisible = match ($context) {
            'create' => $action->isVisibleOnCreate(),
            'edit' => $action->isVisibleOnEdit(),
            'delete' => $action->isVisibleOnDelete(),
            'show' => $action->isVisibleOnShow(),
            'index' => $action->isVisibleOnIndex(),
            default => true,
        };

        // Se não é visível no contexto, retorna false
        if (!$isVisible) {
            return false;
        }

        // Verifica permissões usando o sistema de visibilidade
        return $action->isVisible();
    }


    /**
     * Placeholder para métodos que devem ser implementados pelo controller
     */
    abstract protected function getRouteNameBase(): string;
    abstract protected function getImportOptions(): array;
    abstract protected function getExportOptions(): array;
}
