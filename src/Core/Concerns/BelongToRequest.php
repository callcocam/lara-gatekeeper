<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

trait BelongToRequest
{

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Set the request instance.
     *
     * @param \Illuminate\Http\Request $request
     * @return self
     */
    public function request($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the request instance.
     *
     * @return \Illuminate\Http\Request
     */
    public function getRequest()
    {
        if (!$this->request) {
            $this->request = request();
        }
        return $this->request;
    }

    /**
     * Get the request data.
     *
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->getRequest()->all();
    }

    /**
     * Get a specific value from the request.
     *
     * @param string $key
     * @return mixed
     */
    public function getRequestValue(string $key)
    {
        return $this->getRequest()->input($key);
    }

    /**
     * Check if the request has a specific key.
     *
     * @param string $key
     * @return bool
     */
    public function hasRequestKey(string $key): bool
    {
        return $this->getRequest()->has($key);
    }

    /**
     * Get the request instance.
     *
     * @return \Illuminate\Http\Request|null
     */
    public function getRequestInstance()
    {
        return $this->request;
    }
}
