<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support;

use Callcocam\LaraGatekeeper\Core\Cast\CastRegistry;
use Callcocam\LaraGatekeeper\Core\Concerns;
use Callcocam\LaraGatekeeper\Core\Concerns\EvaluatesClosures;
use Callcocam\LaraGatekeeper\Core\Support\Table\Concerns\HasSearchable;
use Callcocam\LaraGatekeeper\Core\Support\Table\Concerns\HasSortable;
use Closure;

class Column
{
    use EvaluatesClosures;
    use Concerns\BelongsToLabel;
    use Concerns\BelongsToName;
    use Concerns\BelongsToId;
    use Concerns\BelongsToOptions;
    use HasSortable;
    use HasSearchable;

    public ?string $accessorKey = null;
    public bool $isHtml = false; // Para indicar se a célula retorna HTML bruto

    /**
     * Formatação da coluna
     *
     * @var Closure|string|null
     */
    protected Closure|string|null $formatUsing = null;

    /** 
     * @var string|null
     */
    protected ?string $castUsing = null;


    protected Closure|string|null $component = "TextColumn";

    protected function __construct(string $label, ?string $accessorKey = null)
    {
        $this->label = $label;
        $this->accessorKey = $accessorKey ?? strtolower(str_replace(' ', '_', $label));
        $this->id($this->accessorKey);
        $this->nameFormatter($this->accessorKey . '_formatted');
        $this->name($this->accessorKey);
    }

    public static function make(string $label, ?string $accessorKey = null): self
    {
        return new static($label, $accessorKey);
    }


    public function accessorKey(?string $key): self
    {
        $this->accessorKey = $key;
        return $this;
    }

    public function getAccessorKey(): ?string
    {
        return $this->accessorKey;
    }

    public function component(Closure|string $component): self
    {
        $this->component = $component;
        return $this;
    }

    public function getComponent(): ?string
    {
        return $this->component;
    }

    public function hideable(bool $enableHiding = true): self
    {
        $this->enableHiding = $enableHiding;
        return $this;
    }

    public function formatter(string $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }


    public function cell(Closure $callback): self
    {
        $this->cellCallback = $callback;
        return $this;
    }

    public function html(bool $isHtml = true): self
    {
        $this->isHtml = $isHtml;
        return $this;
    }


    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'nameFormatter' => $this->getNameFormatter(),
            'accessorKey' => $this->getAccessorKey(),
            'label' => $this->getLabel(),
            'header' => $this->getLabel(), // Deprecated use 'label' instead
            'sortable' => $this->isSortable(),
            'searchable' => $this->isSearchable(),
            'component' => $this->getComponent(),
            'options' => $this->getOptions()
        ];

        return $data;
    }
    /**
     * Define a formatação da coluna
     *
     * @param Closure|string $callback
     * @return static
     * @throws \Exception
     */
    public function formatUsing(Closure|string $callback): static
    {
        $this->formatUsing = $callback;
        return $this;
    }

    /**
     * Retorna a formatação da coluna
     *
     * @return Closure|string|null
     */
    public function getFormatUsing(): Closure|string|null
    {
        return $this->formatUsing;
    }
    /**
     * Verifica se a coluna está formatada
     *
     * @return bool
     */
    public function isFormatted(): bool
    {
        return !is_null($this->formatUsing);
    }
    /**
     * Formata o valor da coluna
     *
     * @param mixed $value
     * @return mixed
     */
    public function format(mixed $value): mixed
    {

        if ($this->isFormatted()) {
            return $this->evaluate($this->getFormatUsing(), ['value' => $value]);
        }
        return $value;
    }
    /**
     * Define o cast da coluna
     *
     * @param string $cast
     * @param int $priority
     * @return static
     */
    public function castUsing(string $cast, int $priority = 99): static
    {
        CastRegistry::registerFieldCast(
            $this->getName(),
            $cast,
            $priority
        );
        return $this;
    }
    /**
     * Retorna o cast da coluna
     *
     * @return string|null
     */
    public function getCastUsing(): ?string
    {
        return $this->castUsing;
    }
    /**
     * Verifica se a coluna está com cast definido
     *
     * @return bool
     */
    public function isCasted(): bool
    {
        return !is_null($this->castUsing);
    }
    /**
     * Formata o valor da coluna usando o cast
     *
     * @param mixed $value
     * @return mixed
     */
    public function cast(mixed $value): mixed
    {
        if ($this->isCasted()) {
            return app($this->getCastUsing())->setValue($value)->format();
        }
        return $value;
    }


    public function setActions(array $actions = []): self
    {
        $this->actions = $actions;
        return $this;
    }

    // Atalho para a coluna de Ações
    public static function actions(array $actions = []): self
    {
        return static::make('Ações')
            ->id('actions') // Define ID específico
            ->name('actions')
            ->accessorKey('actions') // Ações não têm accessorKey
            ->sortable(false)
            ->hideable(false)
            ->setActions($actions);
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function hasActions(): bool
    {
        return !empty($this->actions);
    }
}
