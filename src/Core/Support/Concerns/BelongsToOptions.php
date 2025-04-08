<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br 
 * 
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;


use Closure;

trait BelongsToOptions
{
    /**
     * As opções do campo ou filtro.
     *
     * @var array
     */
    protected array|Closure $options = [];

    /**
     * Define as opções.
     *
     * @param  array|callable  $options
     * @return $this
     */
    public function options(array|Closure $options): static
    {
        
        if (is_array($options)) {
            foreach ($options as $key => $value) {
                $this->options[] = [
                    'label' => $value,
                    'value' => $key
                ];
            } 
        } else {
            $this->options = $options;
        }

        return $this;
    }

    /**
     * Retorna as opções.
     *
     * @return array | Closure  
     */
    public function getOptions(): array | Closure
    {
        return  $this->options;
    }
}
