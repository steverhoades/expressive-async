<?php
namespace ExpressiveAsync;

use ExpressiveAsync\Emitter\AsyncEmitter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Expressive\Application as ExpressiveApplication;
use Zend\Stratigility\Next;
use Zend\Stratigility\Http;

/**
 * Class Application
 *
 * This class overloads the Zend\Expressive\Application class to insure that the response is writable to the current
 * connection.  It was necessary because the $emitter property is declared as private on Zend\Expressive\Application.
 *
 * @package ExpressiveAsync
 */
class Application extends ExpressiveApplication
{
    /**
     * @var AsyncEmitter
     */
    private $emitter;

    /**
     * For each new connection we need to return a new instance of AsyncEmitter on the Zend\Expressive\Application
     * object.  This is so that the response can be written to the correct connection.
     *
     * @param $connection
     * @return Application
     */
    public function getApplicationForConnection($connection)
    {
        $newapp = clone $this;
        $newapp->emitter = new AsyncEmitter($connection);;

        return $newapp;
    }

    /**
     * Overload the parent as we need the emitter on this application and not the parents.
     *
     * @return mixed
     */
    public function getEmitter()
    {
        return $this->emitter;
    }

    /**
     * Overload middleware invocation.
     *
     * If $out is not provided, uses the result of `getFinalHandler()`.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $out
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $out = null)
    {
        $this->pipeRoutingMiddleware();
        $done = $out ?: $this->getFinalHandler($response);
        $request  = $this->decorateRequest($request);
        $response = $this->decorateResponse($response);

        $next   = new Next($this->pipeline, $done);
        $result = $next($request, $response);

        return (($result instanceof Response || $result instanceof PromiseResponseInterface) ? $result : $response);
    }

    /**
     * Run the application
     *
     * If no request or response are provided, the method will use
     * ServerRequestFactory::fromGlobals to create a request instance, and
     * instantiate a default response instance.
     *
     * It then will invoke itself with the request and response, and emit
     * the returned response using the composed emitter.
     *
     * @param null|ServerRequestInterface $request
     * @param null|ResponseInterface $response
     */
    public function run(ServerRequestInterface $request = null, ResponseInterface $response = null)
    {
        $request  = $request ?: new ServerRequest();
        $response = $response ?: new Response();

        $response = $this($request, $response);


        /**
         * If a deferred was returned, than wait for it to be done and then emit.
         */
        if ($response instanceof PromiseResponseInterface) {
            $response->promise()->done(function(ResponseInterface $response) {
                $emitter = $this->getEmitter();
                $emitter->emit($response);
            });
            return;
        }

        $emitter = $this->getEmitter();
        $emitter->emit($response);
    }

    /**
     * Decorate the Request instance
     *
     * @param Request $request
     * @return Http\Request
     */
    private function decorateRequest(ServerRequestInterface $request)
    {
        if ($request instanceof Http\Request) {
            return $request;
        }

        return new Http\Request($request);
    }

    /**
     * Decorate the Response instance
     *
     * @param Response $response
     * @return Http\Response
     */
    private function decorateResponse(ResponseInterface $response)
    {
        if ($response instanceof Http\Response) {
            return $response;
        }

        return new Http\Response($response);
    }
}
