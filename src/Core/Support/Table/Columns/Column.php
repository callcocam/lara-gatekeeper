<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Columns;

use Callcocam\LaraGatekeeper\Core\Support\Concerns;
use Callcocam\LaraGatekeeper\Core\Support\Table\Columns\Traits;

class Column
{
    use Concerns\BelongsToName;
    use Concerns\BelongsToLabel;
    use Traits\BelongsToSortable;
    use Traits\BelongsToSearchable;
    use Traits\BelongsToFormat;
    use Traits\HasFormatState;
    use Traits\HasRelationship;
    use Traits\WithColumnSortForQuery;
    public function __construct(string $name, ?string $label = null)
    {
        $this->name = $name;
        $this->label = $label ?? str($name)->title()->toString();
    }

    public static function make(string $name, ?string $label = null): static
    {
        return new static($name, $label);
    }
 

    public function toArray(): array
    {
        return [
            'key' => $this->name,
            'name' => $this->name,
            'label' => $this->label,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
        ];
    }
} 