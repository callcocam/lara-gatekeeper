<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

trait HasScreen
{

    protected function getFullWidthCreateForm(): bool
    {
        return false;
    }

    protected function getFullWidthEditForm(): bool
    {
        return false;
    }

    protected function getFullWidthShowPage(): bool
    {
        return false;
    }

    protected function getFullWidthIndexPage(): bool
    {
        return false;
    }

    protected function getToArrayHasScreen(): array
    {
        return [
            'full_width_create_form' => $this->getFullWidthCreateForm(),
            'full_width_edit_form' => $this->getFullWidthEditForm(),
            'full_width_show_page' => $this->getFullWidthShowPage(),
            'full_width_index_page' => $this->getFullWidthIndexPage(),
        ];
    }
}
