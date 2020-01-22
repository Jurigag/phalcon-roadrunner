<?php

namespace Phalcon\RoadRunner\Factory;

use Phalcon\Mvc\Application;
use Phalcon\Mvc\Micro;
use Phalcon\RoadRunner\Exception\InvalidApplicationTypeException;

class BridgeFactory
{
    public static function createByPhalconInstance($phalconApplication)
    {
        if ($phalconApplication instanceof Application) {

        } elseif($phalconApplication instanceof Micro) {

        } else {
            throw new InvalidApplicationTypeException("Application of class ".get_class($phalconApplication). " not supported");
        }
    }
}
