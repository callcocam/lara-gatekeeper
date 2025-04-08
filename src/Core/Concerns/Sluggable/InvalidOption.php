<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns\Sluggable;

use Exception;

class InvalidOption extends Exception
{
    public static function missingFromField()
    {
        return new static('Could not determine which fields should be sluggified');
    }

    public static function missingSlugField()
    {
        return new static('Could not determine in which field the slug should be saved');
    }

    public static function invalidMaximumLength()
    {
        return new static('Maximum length should be greater than zero');
    }
}