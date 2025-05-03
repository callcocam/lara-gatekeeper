<?php

namespace Callcocam\LaraGatekeeper\Core\Support;

use Closure;

class Column
{
    public string $id;
    public ?string $accessorKey = null;
    public string $header;
    public bool $sortable = false;
    public bool $enableHiding = true;
    public ?string $formatter = null;
    public mixed $formatterOptions = null;
    public ?Closure $cellCallback = null;
    public bool $isHtml = false; // Para indicar se a célula retorna HTML bruto

    protected function __construct(string $header, ?string $accessorKey = null)
    {
        $this->header = $header;
        $this->accessorKey = $accessorKey ?? strtolower(str_replace(' ', '_', $header));
        $this->id = $this->accessorKey; // ID padrão baseado na chave de acesso
    }

    public static function make(string $header, ?string $accessorKey = null): self
    {
        return new static($header, $accessorKey);
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

    public function accessorKey(?string $key): self
    {
        $this->accessorKey = $key;
        return $this;
    }

    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;
        return $this;
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

    public function options(mixed $options): self
    {
        $this->formatterOptions = $options;
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

    // Atalho para a coluna de Ações
    public static function actions(): self
    {
        return static::make('Ações')
            ->id('actions') // Define ID específico
            ->accessorKey(null) // Ações não têm accessorKey
            ->sortable(false)
            ->hideable(false);
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'accessorKey' => $this->accessorKey,
            'header' => $this->header,
            'sortable' => $this->sortable,
            'enableHiding' => $this->enableHiding,
        ];

        if ($this->formatter) {
            $data['formatter'] = $this->formatter;
            if ($this->formatterOptions !== null) {
                $data['formatterOptions'] = $this->formatterOptions;
            }
        }

        // A célula só é enviada se houver um callback E não for um formatador padrão
        // Ou se for explicitamente HTML (para casos como o avatar)
        // A lógica de renderização padrão/formatador agora está no frontend
        if ($this->cellCallback && !$this->formatter) {
             // Não podemos serializar Closures diretamente para JSON.
             // O frontend não usará mais a 'cell' do backend para lógica complexa,
             // exceto talvez para HTML simples ou se a lógica de formatação estiver aqui.
             // Por enquanto, vamos omitir a serialização da closure.
             // Se precisar passar HTML formatado, use ->html() e formate aqui.
             // $data['cell'] = 'Closure defined'; // Placeholder ou omitir
             if ($this->isHtml) {
                 // Potencialmente, poderíamos executar o callback aqui se soubéssemos o $row,
                 // mas isso complica a definição. Melhor deixar a lógica de cell complexa
                 // para ser definida no frontend ou usar formatters.
                 $data['html'] = true; // Sinaliza para o frontend que a renderização é customizada
             }
        } elseif ($this->isHtml) {
            // Se é HTML mas não tem callback (ex: header HTML simples)
             $data['html'] = true;
        }


        return $data;
    }
} 