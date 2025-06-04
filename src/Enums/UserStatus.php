<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Enums;

enum UserStatus: string
{
    case Published = 'published';
    case Draft = 'draft';
    
    public function label(): string
    {
        return match($this) {
            self::Published => 'Publicado',
            self::Draft => 'Rascunho',
        };
    }
    
    public static function options(): array
    {
        return collect(self::cases())
        ->map(fn($case) => ['value' => $case->value, 'label' => $case->label()])
        ->values()
        ->toArray();
    }

    public static function getOptions(): array
    {
        return [
            self::Published->value => self::Published->label(),
            self::Draft->value => self::Draft->label(),
        ];
    }

    public static function variantOptions(): array
    {
        return [
            static::Published->value => 'success',
            static::Draft->value => 'secondary',
        ];
    }
} 