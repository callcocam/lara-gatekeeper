<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br 
 * 
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasBreadcrumbs
{
    /** @var array */
    protected array $breadcrumbs = [];

    /** @var array */
    protected array $breadcrumbsDefault = [];

    /**
     * @param string $label
     * @param string|null $url
     * @return $this
     */
    public function breadcrumb(string $label, ?string $url = null): static
    {
        $this->breadcrumbs[] = [
            'title' => $label,
            'href' => $url,
            'active' => false,
        ];

        return $this;
    }

    /**
     * @param string $label
     * @param string|null $url
     * @return $this
     */
    public function active(): static
    {
        $this->breadcrumbs[array_key_last($this->breadcrumbs)]['active'] = true;
        return $this;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getBreadcrumbs(): array
    {
        return array_merge($this->getBreadcrumbsDefault(), $this->breadcrumbs);
    }

    /**
     * @return bool
     * @throws \Exception 
     */
    public function hasBreadcrumbs(): bool
    {
        return count($this->breadcrumbs) > 0;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getBreadcrumbsDefault(): array
    {
        return array_merge([
            [
                'title' => __('Dashboard'),
                'href' => route('dashboard'),
                'active' => false,
            ],
        ], $this->breadcrumbsDefault);
    }
}
