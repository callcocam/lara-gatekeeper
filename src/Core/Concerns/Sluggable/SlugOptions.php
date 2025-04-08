<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns\Sluggable;


class SlugOptions
{
    /** @var array|callable */
    public $generateSlugFrom;

    public $slugField;

    public $generateUniqueSlugs = true;

    public $maximumLength = 250;

    public $generateSlugsOnCreate = true;

    public $generateSlugsOnUpdate = true;

    public $slugSeparator = '-';

    public $slugLanguage = 'pt_BR';

    public static function create(): self
    {
        return new static();
    }

    /**
     * @return string | false
     */
    protected function slugTo()
    {
        return config("sluggable.slug", "slug");
    }

    /**
     * @return string
     */
    protected function slugFrom()
    {
        return config("sluggable.name", "name");
    }

    /**
     * @param string|array|callable $fieldName
     *
     * @return \App\Sluggable\SlugOptions
     */
    public function generateSlugsFrom($fieldName): self
    {
        if (is_string($fieldName)) {
            $fieldName = [$fieldName];
        }

        $this->generateSlugFrom = $fieldName;

        return $this;
    }

    public function saveSlugsTo(string $fieldName): self
    {
        $this->slugField = $fieldName;

        return $this;
    }

    public function allowDuplicateSlugs(): self
    {
        $this->generateUniqueSlugs = false;

        return $this;
    }

    public function slugsShouldBeNoLongerThan(int $maximumLength): self
    {
        $this->maximumLength = $maximumLength;

        return $this;
    }

    public function doNotGenerateSlugsOnCreate(): self
    {
        $this->generateSlugsOnCreate = false;

        return $this;
    }

    public function doNotGenerateSlugsOnUpdate(): self
    {
        $this->generateSlugsOnUpdate = false;

        return $this;
    }

    public function usingSeparator(string $separator): self
    {
        $this->slugSeparator = $separator;

        return $this;
    }

    public function usingLanguage(string $language): self
    {
        $this->slugLanguage = $language;

        return $this;
    }
}
