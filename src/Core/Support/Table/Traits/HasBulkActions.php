<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Traits;

use Callcocam\LaraGatekeeper\Core\Support\Table\Actions\Bulk\BulkAction;

trait HasBulkActions
{
    /** @var BulkAction[] */
    protected array $bulkActions = [];

    public function addBulkAction(BulkAction $action): static
    {
        $this->bulkActions[] = $action;
        return $this;
    }

    public function getBulkActions(): array
    {
        return $this->bulkActions;
    }

    public function bulkActions(array $bulkActions): static
    {
        foreach ($bulkActions as $bulkAction) {
            $this->addBulkAction($bulkAction);
        }
        return $this;
    }

    public function withDefaultBulkActions(): static
    {
        $this->addBulkAction(
            BulkAction::make('Excluir', 'delete')
                ->icon('Trash2')
                ->variant('destructive')
                ->requireConfirmation(
                    true,
                    'Confirmar Exclusão',
                    'Esta ação não pode ser desfeita. Isso excluirá permanentemente os itens selecionados.'
                )
        );

        $this->addBulkAction(
            BulkAction::make('Exportar', 'export')
                ->icon('Download')
        );

        $this->addBulkAction(
            BulkAction::make('Enviar Email', 'email')
                ->icon('Mail')
        );

        return $this;
    }
}
