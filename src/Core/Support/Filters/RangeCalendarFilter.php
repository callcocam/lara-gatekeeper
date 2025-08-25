<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Filters;

use Callcocam\LaraGatekeeper\Core\Support\Filter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class RangeCalendarFilter extends Filter
{
    protected string $component = 'RangeCalendarFilter';

    public function __construct(string $label, ?string $name = null)
    {
        parent::__construct($label, $name);

        // Define o formatUsing para processar range de datas
        $this->formatUsing(function (Builder $query, $value) {
            $value = json_decode($value, true);
            if (is_array($value) && isset($value['start']) && isset($value['end'])) {
                if ($value['start'] && $value['end']) {
                    $query->whereBetween($this->getName(), [
                        $value['start'],
                        $value['end']
                    ]);
                } elseif ($value['start']) {
                    $query->where($this->getName(), '>=', $value['start']);
                } elseif ($value['end']) {
                    $query->where($this->getName(), '<=', $value['end']);
                }
            }
        });
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => [
                'start' => $this->getStartDate(),
                'end' => $this->getEndDate(),
            ],
        ]);
    }

    protected function getStartDate(): ?string
    {
        if ($this->getRequest()->has($this->getName())) {
            $value = json_decode($this->getRequest()->input($this->getName()), true);
            if ($value && isset($value['start'])) {
                return Carbon::parse($value['start'])->format('Y-m-d');
            }
        }
        return '';
    }

    protected function getEndDate(): ?string
    {
        if ($this->getRequest()->has($this->getName())) {
            $value = json_decode($this->getRequest()->input($this->getName()), true);
            if ($value && isset($value['end'])) {
                return Carbon::parse($value['end'])->format('Y-m-d');
            }
        }
        return '';
    }
}
