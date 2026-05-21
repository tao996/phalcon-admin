<?php

namespace Phax\Bridge\Traits;

use Phax\Bridge\HttpDi;

trait HttpDiTrait
{
    protected HttpDi $httpDi;

    /**
     * @param HttpDi $httpDi
     */
    public function setHttpDi(HttpDi $httpDi): void
    {
        $this->httpDi = $httpDi;
        if (property_exists($this, 'container')) {
            $this->container = $this->httpDi;
        } elseif (method_exists($this, 'setDI')){
            $this->setDI($this->httpDi);
        }
    }

    public function getHttpDi(): HttpDi
    {
        return $this->httpDi;
    }


}