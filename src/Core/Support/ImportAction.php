<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support;

use Callcocam\LaraGatekeeper\Core;
use Closure;
use Illuminate\Http\Request;

class ImportAction extends Action
{
    use Core\Concerns\FactoryPattern;
    use Core\Concerns\BelongsToOptions;
    use Core\Concerns\BelongsToType;
    use Core\Concerns\BelongsToModal;

    protected string $component = 'ImportAction';

    protected Closure|string|null $fileName = 'file';

    public function __construct(string $name, ?string $header = null)
    {
        parent::__construct($name, $header);
        $this->type('import');
        $this->modalHeading($header);

        $this->action(function (Request $request) {
            $file = $request->file($this->getFieldName());
            // Lógica para processar o arquivo de importação 
            return redirect()->back()->with('success', 'Ação de importação basica, você pode personalizar. não esqueça de implementar a lógica de importação.');
        });

        $this->modalDescription('Selecione um arquivo para importar a estrutura mercadológica.');

        $this->fields([
            Field::make('file', 'Arquivo')
                ->type('file')
        ]);
    }
    /**
     * Formatação da coluna
     *
     * @var Closure|string|null
     */
    protected Closure|string|null $formatUsing = null;

    public function fieldName(Closure|string|null $name): self
    {
        $this->fileName = $name;
        return $this;
    }

    public function getFieldName(): ?string
    {
        return $this->evaluate($this->fileName);
    }

    public function render($item = null): array
    {

        return array_merge(parent::render($item), [
            'type' => $this->getType(),
            'modal' => $this->getModal(),
        ]);
    }
}
