<?php

namespace Phalcon\RoadRunner\Bridge;

use Psr\Http\Message\ServerRequestInterface;
use Spiral\Goridge\StreamRelay;
use Spiral\RoadRunner\PSR7Client;
use Spiral\RoadRunner\Worker;

abstract class AbstractBridge implements BridgeInterface
{
    /**
     * @var \Phalcon\Mvc\Application|\Phalcon\Mvc\Micro
     */
    protected $application;

    /**
     * @var array|callable[]
     */
    protected $afterThrowable = [];

    /**
     * @var array|callable[]
     */
    protected $afterException = [];

    /**
     * @var array|callable[]
     */
    protected $afterResponse = [];

    /**
     * @var array|callable[]
     */
    protected $afterFinally = [];

    public function __construct()
    {
        $this->afterFinally[] = function () {
            $this->clearSession();
        };
    }

    protected function prepareSession()
    {

    }

    /**
     * This method will parse psr7 request, set it to global variables so phalcon can handle it and
     *
     * @return void
     */
    public function waitAndHandle(): void
    {
        $relay = new StreamRelay(STDIN, STDOUT);
        $psr7 = new PSR7Client(new Worker($relay));

        while ($req = $psr7->acceptRequest()) {
            try {
                $this->prepareSession();
                $response = new \Zend\Diactoros\Response();
                $_GET = $req->getQueryParams();
                $body = $req->getParsedBody();
                if (is_array($body)) {
                    $_POST = $body;
                }
                $_COOKIE = $req->getCookieParams();
                $_SERVER = $req->getServerParams();
                $_FILES = $req->getUploadedFiles();

                foreach ($this->afterResponse as $callable) {
                    $callable($this->application);
                }
                $content = $this->internalHandle($req);
                $response->getBody()->write($content);
                $psr7->respond($response);
            } catch (\Exception $e) {
                $print = true;
                foreach ($this->afterException as $callable) {
                    $print = $callable($this->application, $e);
                }

                if ($print === true) {
                    $psr7->getWorker()->error($e->getMessage(). " ".$e->getTraceAsString());
                }
            } catch (\Throwable $e) {
                $print = true;
                foreach ($this->afterThrowable as $callable) {
                    $print = $callable($this->application, $e);
                }

                if ($print === true) {
                    $psr7->getWorker()->error($e->getMessage(). " ".$e->getTraceAsString());
                }
            } finally {
                $this->clearDiAndControllers();
            }
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    abstract protected function internalHandle(ServerRequestInterface $request): string;


    /**
     * @param callable $after
     * @return static
     */
    public function afterThrowable(callable $after): AbstractBridge
    {
        $this->afterThrowable[] = $after;

        return $this;
    }

    /**
     * @param callable $after
     * @return static
     */
    public function afterException(callable $after): AbstractBridge
    {
        $this->afterException[] = $after;

        return $this;
    }

    /**
     * @param callable $after
     * @return static
     */
    public function afterResponse(callable $after): AbstractBridge
    {
        $this->afterException[] = $after;

        return $this;
    }

    /**
     * @param callable $after
     * @return static
     */
    public function afterFinally(callable $after): AbstractBridge
    {
        $this->afterFinally[] = $after;

        return $this;
    }

    protected function clearSession()
    {

    }
}
