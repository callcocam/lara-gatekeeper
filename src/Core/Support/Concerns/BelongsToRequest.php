<?php

/**
 *  Created by Claudio Campos.
 *  User: callcocam@gmail.com, contato@sigasmart.com.br
 *  https://www.sigasmart.com.br
 * 
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Illuminate\Http\Request;

trait BelongsToRequest
{
    /**
     * The request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * Set the request instance.
     *
     * @param  Request  $request
     * @return $this
     */
    public function withRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the request instance.
     * 
     * @return Request
     * 
     */
    public function getRequest()
    {
        return $this->request;
    }
}
