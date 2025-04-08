<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Enums;

enum TenantStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case INACTIVE = 'inactive';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Rascunho',
            self::PUBLISHED => 'Ativo',
            self::INACTIVE => 'Inativo',
        };
    }
}
