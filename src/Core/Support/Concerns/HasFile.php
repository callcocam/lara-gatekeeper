<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasFile
{
    /**
     * Set accepted file types
     * 
     * @param string $accept Comma-separated list of MIME types or extensions
     * @return $this
     */
    public function accept(string $accept): self
    {
        $this->accept = $accept;
        return $this;
    }

    /**
     * Allow multiple file uploads
     * 
     * @param bool $multiple
     * @return $this
     */
    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * Set maximum file size in bytes
     * 
     * @param int $maxSize
     * @return $this
     */
    public function maxSize(int $maxSize): self
    {
        $this->inputProps['maxSize'] = $maxSize;
        return $this;
    }

    /**
     * Set maximum number of files
     * 
     * @param int $maxFiles
     * @return $this
     */
    public function maxFiles(int $maxFiles): self
    {
        $this->inputProps['maxFiles'] = $maxFiles;
        return $this;
    }

    /**
     * Set upload mode to dropzone
     * 
     * @param bool $dropzone
     * @return $this
     */
    public function dropzone(bool $dropzone = true): self
    {
        if ($dropzone) {
            $this->type = 'dropzone';
        } else {
            $this->type = 'file';
        }
        return $this;
    }

    /**
     * Set upload endpoint URL
     * 
     * @param string $url
     * @return $this
     */
    public function uploadUrl(string $url): self
    {
        $this->inputProps['uploadUrl'] = $url;
        return $this;
    }

    /**
     * Set to image upload mode
     * 
     * @param bool $image
     * @return $this
     */
    public function image(bool $image = true): self
    {
        if ($image) {
            $this->type = 'image';
            $this->accept = 'image/*';
        } 
        return $this;
    }

    /**
     * Enable image preview
     * 
     * @param bool $preview
     * @return $this
     */
    public function preview(bool $preview = true): self
    {
        $this->inputProps['preview'] = $preview;
        return $this;
    }

    /**
     * Set to avatar/profile image mode
     * 
     * @param bool $avatar
     * @return $this
     */
    public function avatar(bool $avatar = true): self
    {
        if ($avatar) {
            $this->type = 'avatar';
            $this->accept = 'image/*';
        }
        return $this;
    }
}