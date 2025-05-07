<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasTypes
{
    /**
     * Set field type to text
     * 
     * @return $this
     */
    public function text(): self
    {
        $this->type('text');
        return $this;
    }

    /**
     * Set field type to textarea
     * 
     * @return $this
     */
    public function textarea(): self
    {
        $this->type = 'textarea';
        return $this;
    }

    /**
     * Set field type to select
     * 
     * @return $this
     */
    public function select(): self
    {
        $this->type = 'select';
        return $this;
    }

    /**
     * Set field type to checkbox
     * 
     * @return $this
     */
    public function checkbox(): self
    {
        $this->type = 'checkbox';
        return $this;
    }

    /**
     * Set field type to radio
     * 
     * @return $this
     */
    public function radio(): self
    {
        $this->type = 'radio';
        return $this;
    }

    /**
     * Set field type to password
     * 
     * @return $this
     */
    public function password(): self
    {
        $this->type = 'password';
        $this->inputProps['autocomplete'] = 'new-password';
        $this->inputProps['type'] = 'password';
        return $this;
    }

    /**
     * Set field type to email
     * 
     * @return $this
     */
    public function email(): self
    {
        $this->type = 'email'; 
        $this->inputProps['type'] = 'email';
        return $this;
    }

    /**
     * Set field type to number
     * 
     * @return $this
     */
    public function number(): self
    {
        $this->type = 'number';
        $this->inputProps['type'] = 'number';
        return $this;
    }

    /**
     * Set field type to date
     * 
     * @return $this
     */
    public function date(): self
    {
        $this->type = 'date';
        $this->inputProps['type'] = 'date';
        return $this;
    }

    /**
     * Set field type to datetime-local
     * 
     * @return $this
     */
    public function datetime(): self
    {
        $this->type = 'datetime-local';
        $this->inputProps['type'] = 'datetime-local';
        return $this;
    }

    /**
     * Set field type to file
     * 
     * @return $this
     */
    public function file(): self
    {
        $this->type = 'file';
        return $this;
    }

    /**
     * Set field type to hidden
     * 
     * @return $this
     */
    public function hidden(): self
    {
        $this->type = 'hidden';
        $this->inputProps['type'] = 'hidden';
        return $this;
    }

    /**
     * Set field type to color
     * 
     * @return $this
     */
    public function color(): self
    {
        $this->type = 'color';
        $this->inputProps['type'] = 'color';
        return $this;
    }

    /**
     * Set field type to tel
     * 
     * @return $this
     */
    public function tel(): self
    {
        $this->type = 'tel';
        $this->inputProps['type'] = 'tel';
        return $this;
    }

    /**
     * Set field type to url
     * 
     * @return $this
     */
    public function url(): self
    {
        $this->type = 'url';
        $this->inputProps['type'] = 'url';
        return $this;
    }
}