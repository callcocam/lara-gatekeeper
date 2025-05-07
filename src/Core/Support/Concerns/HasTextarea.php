<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasTextarea
{
    /**
     * Set textarea rows
     * 
     * @param int $rows
     * @return $this
     */
    public function rows(int $rows): self
    {
        $this->inputProps['rows'] = $rows;
        return $this;
    }

    /**
     * Set textarea to autosize
     * 
     * @param bool $autosize
     * @return $this
     */
    public function autosize(bool $autosize = true): self
    {
        $this->inputProps['autosize'] = $autosize;
        return $this;
    }

    /**
     * Set textarea max height
     * 
     * @param string $maxHeight
     * @return $this
     */
    public function maxHeight(string $maxHeight): self
    {
        $this->inputProps['maxHeight'] = $maxHeight;
        return $this;
    }

    /**
     * Set textarea min height
     * 
     * @param string $minHeight
     * @return $this
     */
    public function minHeight(string $minHeight): self
    {
        $this->inputProps['minHeight'] = $minHeight;
        return $this;
    }

    /**
     * Set textarea to rich text editor mode
     * 
     * @param bool $richText
     * @return $this
     */
    public function richText(bool $richText = true): self
    {
        if ($richText) {
            $this->type = 'rich-text';
        } else {
            $this->type = 'textarea';
        }
        return $this;
    }

    /**
     * Set textarea to markdown editor mode
     * 
     * @param bool $markdown
     * @return $this
     */
    public function markdown(bool $markdown = true): self
    {
        if ($markdown) {
            $this->type = 'markdown';
        } else {
            $this->type = 'textarea';
        }
        return $this;
    }
}