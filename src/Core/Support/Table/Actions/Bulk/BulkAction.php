<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Actions\Bulk;

use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToLabel;
use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToIcon;
use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToVariant; 
use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToUrl;
use Closure;

class BulkAction
{ 
    use BelongsToLabel;
    use BelongsToIcon;
    use BelongsToVariant; 
    use BelongsToUrl;
    protected string | Closure | null $action = 'delete'; 
    protected bool $requireConfirmation = false;
    protected ?string $confirmationTitle = "Confirmar Ação  ";
    protected ?string $confirmationMessage = "Esta ação não pode ser desfeita. Isso excluirá permanentemente os itens selecionados.";

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    public static function make(string $label): static
    {
        return new static($label);
    }
  
    public function action(string | Closure $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): string | Closure | null
    {
        return $this->action;
    }


    public function requireConfirmation(bool $value, ?string $title = null, ?string $message = null): static
    {
        $this->requireConfirmation = $value;
        $this->confirmationTitle = $title;
        $this->confirmationMessage = $message;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'label' => $this->getLabel(),
            'action' => $this->getAction(),
            'icon' => $this->getIcon(),
            'variant' => $this->getVariant(),
            'requireConfirmation' => $this->requireConfirmation,
            'confirmationTitle' => $this->confirmationTitle,
            'confirmationMessage' => $this->confirmationMessage,
        ];
    }
}
