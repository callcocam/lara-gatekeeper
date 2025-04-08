<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Traits;

use Callcocam\LaraGatekeeper\Core\Support\Table\Columns\Column;

trait HasColumns
{
    protected array $columns = [];

    public function addColumn(Column $column): static
    {
        $this->columns[] = $column;
        return $this;
    }

    public function columns(array $columns): static
    {
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }
}
