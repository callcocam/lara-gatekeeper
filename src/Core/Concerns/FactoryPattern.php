<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

/**
 * Trait para implementar o padrão Factory
 */
trait FactoryPattern
{
    /**
     * Cria uma nova instância da classe
     */
    public static function make(...$arguments): static
    {
        return new static(...$arguments);
    }

    /**
     * Cria uma nova instância da classe com os dados fornecidos
     */
    public static function create(array $data = []): static
    {
        $instance = new static();
        $instance->fill($data);
        return $instance;
    }

    /**
     * Preenche a instância com os dados fornecidos
     */
    public function fill(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Executa a lógica de criação e retorna a instância
     */
    public function execute(): static
    {
        $this->data = $this->query->paginate(
            $this->getConfig('per_page', 15),
            ['*'],
            'page',
            $this->getConfig('page', 1)
        );
        // Aqui você pode adicionar lógica adicional antes de retornar a instância
        return $this;
    }
}
