<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasValidation
{
    /**
     * Set field as required
     * 
     * @param bool $required
     * @param string|null $message Custom error message
     * @return $this
     */
    public function required(bool $required = true, ?string $message = null): self
    {
        $this->required = $required;
        if ($message) {
            $this->inputProps['requiredMessage'] = $message;
        }
        return $this;
    }

    /**
     * Set field as nullable
     * 
     * @param bool $nullable
     * @return $this
     */
    public function nullable(bool $nullable = true): self
    {
        $this->inputProps['nullable'] = $nullable;
        return $this;
    }

    /**
     * Set min value/length validation
     * 
     * @param int $min
     * @param string|null $message Custom error message
     * @return $this
     */
    public function min(int $min, ?string $message = null): self
    {
        $this->inputProps['min'] = $min;
        if ($message) {
            $this->inputProps['minMessage'] = $message;
        }
        return $this;
    }

    /**
     * Set max value/length validation
     * 
     * @param int $max
     * @param string|null $message Custom error message
     * @return $this
     */
    public function max(int $max, ?string $message = null): self
    {
        $this->inputProps['max'] = $max;
        if ($message) {
            $this->inputProps['maxMessage'] = $message;
        }
        return $this;
    }

    /**
     * Set regex pattern validation
     * 
     * @param string $pattern
     * @param string|null $message Custom error message
     * @return $this
     */
    public function pattern(string $pattern, ?string $message = null): self
    {
        $this->inputProps['pattern'] = $pattern;
        if ($message) {
            $this->inputProps['patternMessage'] = $message;
        }
        return $this;
    }

    /**
     * Set email validation
     * 
     * @param bool $isEmail
     * @param string|null $message Custom error message
     * @return $this
     */
    public function isEmail(bool $isEmail = true, ?string $message = null): self
    {
        if ($isEmail) {
            $this->inputProps['isEmail'] = true;
            if ($message) {
                $this->inputProps['emailMessage'] = $message;
            }
        } else {
            unset($this->inputProps['isEmail']);
        }
        return $this;
    }

    /**
     * Set URL validation
     * 
     * @param bool $isUrl
     * @param string|null $message Custom error message
     * @return $this
     */
    public function isUrl(bool $isUrl = true, ?string $message = null): self
    {
        if ($isUrl) {
            $this->inputProps['isUrl'] = true;
            if ($message) {
                $this->inputProps['urlMessage'] = $message;
            }
        } else {
            unset($this->inputProps['isUrl']);
        }
        return $this;
    }

    /**
     * Set custom validation function name
     * 
     * @param string $validatorName
     * @param string|null $message Custom error message
     * @return $this
     */
    public function validator(string $validatorName, ?string $message = null): self
    {
        $this->inputProps['validator'] = $validatorName;
        if ($message) {
            $this->inputProps['validatorMessage'] = $message;
        }
        return $this;
    }
}