<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support;

use Callcocam\LaraGatekeeper\Core;
use Closure;

class ExportAction extends Action
{
    use Core\Concerns\BelongsToIcon;
    use Core\Concerns\BelongsToOptions;
    use Core\Concerns\BelongsToType;
    use Core\Concerns\BelongsToModal;

    protected string $component = 'ExportAction';

    protected function __construct(string $accessorKey, ?string $header = null)
    {
        parent::__construct($accessorKey, $header);
        $this->type('export');
        $this->method('POST');
        $this->modalCancelButtonText('Cancelar');
        $this->modalConfirmButtonText('Exportar');
        $this->modalHeading('Confirmação de Exportação');
        $this->modalDescription('Tem certeza que deseja exportar os dados?');
    }

    public function toArray($item = null): array
    {
        return array_merge(parent::toArray($item), [ 
            'icon' => $this->getIcon(),
            'type' => $this->getType(),
            'confirm' => [
                'title' => $this->getModalHeading(),
                'description' => $this->getModalDescription(),
                'confirmButtonText' => $this->getModalConfirmButtonText(),
                'cancelButtonText' => $this->getModalCancelButtonText(),
            ],
        ]);
    }
}
