<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;

trait HasVisibility
{
    /**
     * Set field display condition
     * 
     * @param bool|Closure $condition
     * @return $this
     */
    public function when(mixed $condition): self
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Set field to only display if another field has a specific value
     * 
     * @param string $field The dependent field name
     * @param mixed $value The value to check for
     * @return $this
     */
    public function whenField(string $field, mixed $value): self
    {
        $this->inputProps['dependsOn'] = [
            'field' => $field,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Set field to be hidden initially
     * 
     * @param bool $hidden
     * @return $this
     */
    public function setHidden(bool $hidden = true): self
    {
        $this->inputProps['hidden'] = $hidden;
        return $this;
    }

    /**
     * Set field to be readonly
     * 
     * @param bool $readonly
     * @return $this
     */
    public function readonly(bool $readonly = true): self
    {
        $this->inputProps['readonly'] = $readonly;
        return $this;
    }

    /**
     * Set field to be disabled
     * 
     * @param bool $disabled
     * @return $this
     */
    public function disabled(bool $disabled = true): self
    {
        $this->inputProps['disabled'] = $disabled;
        return $this;
    }

    /**
     * Show field only for specific user roles
     * 
     * @param array|string $roles
     * @return $this
     */
    public function forRoles(array|string $roles): self
    {
        $this->inputProps['visibleForRoles'] = is_array($roles) ? $roles : [$roles];
        return $this;
    }

    /**
     * Show field only for specific permissions
     * 
     * @param array|string $permissions
     * @return $this
     */
    public function forPermissions(array|string $permissions): self
    {
        $this->inputProps['visibleForPermissions'] = is_array($permissions) ? $permissions : [$permissions];
        return $this;
    }

    /**
     * Set field to only display in certain contexts (create/edit/detail)
     * 
     * @param array|string $contexts
     * @return $this
     */
    public function onlyOn(array|string $contexts): self
    {
        $this->inputProps['visibleOnContexts'] = is_array($contexts) ? $contexts : [$contexts];
        return $this;
    }
}