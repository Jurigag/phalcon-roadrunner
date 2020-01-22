<?php

namespace Phalcon\RoadRunner\Bridge;

/**
 * Interface BridgeInterface
 * @package Phalcon\RoadRunner\Bridge
 */
interface BridgeInterface
{
    /**
     * @return mixed
     */
    public function waitAndHandle();
}
