<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
*/
namespace Callcocam\LaraGatekeeper\Core\Support\Info\Traits;

trait HasGridLayout
{
    protected string | array | int | null $layout = '2';

    protected string | array | int | null $grid = '1';

    public function layout(string | array | int | null $layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    public function columns(string | array | int | null $layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    public function grid(string | array | int | null $grid): static
    {
        $this->grid = $grid;
        return $this;
    }

    public function columnSpan(string | array | int | null $grid): static
    {
        $this->grid = $grid;
        return $this;
    }

    public function columnSpanFull(): static
    {
        $this->grid = 'full';
        return $this;
    }

    public function getLayout(): string | array | int | null
    {
        return $this->layout;
    }

    public function getGrid(): string | array | int | null
    {
        return $this->grid;
    }
}
