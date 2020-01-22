<?php

namespace Phalcon\RoadRunner\Bridge;

use Psr\Http\Message\ServerRequestInterface;

class Micro extends AbstractBridge
{
    public function __construct(\Phalcon\Mvc\Micro $application)
    {
        $this->application= $application;
        $this->afterFinally[] = function() {

        };
    }

    protected function internalHandle(ServerRequestInterface $request): string
    {

        return $this->application->handle()
    }
}
