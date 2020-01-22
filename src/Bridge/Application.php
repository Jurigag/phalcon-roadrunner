<?php

namespace Phalcon\RoadRunner\Bridge;

use Phalcon\Events\Event;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;
use Phalcon\RoadRunner\Exception\MissingEventsManagerException;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Goridge\StreamRelay;
use Spiral\RoadRunner\PSR7Client;
use Spiral\RoadRunner\Worker;

class Application extends AbstractBridge
{
    /**
     * @var array|string[]
     */
    protected $clearProperties = ['request', 'response'];

    /**
     * @var array|Controller[]
     */
    protected $activeControllerInstances = [];

    /**
     * @var bool
     */
    protected $clearControllersProperties;

    /**
     * @var bool
     */
    protected $eventManagerAttached = false;

    /**
     * Application constructor.
     * @param \Phalcon\Mvc\Application $application
     * @param bool $clearControllerProperties
     */
    public function __construct(\Phalcon\Mvc\Application $application, bool $clearControllerProperties = true)
    {
        $this->application = $application;
        $this->clearControllersProperties = $clearControllerProperties;
        $this->afterFinally[] = function () {
            $this->clearDiAndControllers();
        };
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     * @throws MissingEventsManagerException
     */
    protected function internalHandle(ServerRequestInterface $request): string
    {
        $this->collectControllers();

        return $this->application->handle($request->getUri()->getPath())->getContent();
    }

    /**
     * Resets request and response services in di
     * Also clears controller properties
     */
    protected function clearDiAndControllers(): void
    {
        $di = $this->application->getDI();
        /** @var Dispatcher $dispatcher */
        /**
         * Clear shared instances of request and response
         */
        if ($di->has('request')) {
            $service = $di->getService('request');
            $di->remove('request');
            $di->setRaw('request', $service);
        }
        if ($di->has('response')) {
            $service = $di->getService('response');
            $di->remove('response');
            $di->setRaw('response', $service);
        }
        /**
         * Clear request and response properties of active controllers
         */
        if ($this->clearControllersProperties) {
            foreach ($this->activeControllerInstances as $activeControllerInstance) {
                if (!empty($activeControllerInstance)) {
                    foreach ($this->clearProperties as $clearProperty) {
                        if (isset($activeControllerInstance->$clearProperty)) {
                            unset($activeControllerInstance->$clearProperty);
                        }
                    }
                }
            }
        }
    }

    /**
     * @throws MissingEventsManagerException
     */
    protected function collectControllers(): void
    {
        $di = $this->application->getDI();
        if ($di->has('dispatcher')) {
            $this->activeControllerInstances = [];
            if (!$this->eventManagerAttached) {
                /** @var Dispatcher $dispatcher */
                $dispatcher = $di->get('dispatcher');
                $eventManager = $dispatcher->getEventsManager();
                if (empty($eventManager) && $di->has('eventsManager')) {
                    $eventManager = $di->get('eventsManager');
                    $dispatcher->setEventsManager($eventManager);
                } else {
                    throw new MissingEventsManagerException(
                        'For correct working we require that events manager exists(and best set for dispatcher)'
                    );
                }
                $eventManager->attach(
                    'dispatch:beforeExecuteRoute',
                    function (Event $event, Dispatcher $dispatcher) {
                        $this->activeControllerInstances[] = $dispatcher->getActiveController();
                    }
                );
            }
        }
    }

    /**
     * @param array|string[] $clearProperties
     * @return Application
     */
    public function setClearProperties($clearProperties): Application
    {
        $this->clearProperties = $clearProperties;

        return $this;
    }
}
