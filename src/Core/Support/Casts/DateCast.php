<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;

class DateCast implements CastsAttributes
{
    protected string $format;

    /**
     * Constructor para configurar o formato.
     *
     * @param string $format
     */
    public function __construct(string $format = 'Y-m-d')
    {
        $this->format = $format;
    }

    /**
     * Transforma o valor armazenado no banco ao recuperá-lo.
     *
     * @param  mixed  $value
     * @return string|null
     */
    public function get($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return Carbon::parse($value)->format($this->format);
    }

    /**
     * Transforma o valor ao armazená-lo no banco.
     *
     * @param  mixed  $value
     * @return string|null
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
