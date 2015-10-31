<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/30/15
 * Time: 1:41 PM
 */

namespace ExpressiveAsync;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use React\Promise\Promise;

/**
 * Class DeferredResponse
 *
 * Response object that implements the ResponseInterface but is used to defer a Response.  The application listens
 * for a PromiseResponseInterface Response in addition to the ResponseInterface and interacts with the promise object
 * to emit a response once the promise has been resolved.
 *
 * @package ExpressiveAsync
 */
class DeferredResponse implements ResponseInterface, PromiseResponseInterface
{
    /**
     * @var
     */
    protected $promise;

    /**
     * @param Promise $promise
     */
    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }

    /**
     * @return Promise
     */
    public function promise()
    {
        return $this->promise;
    }

    /**
     * ResponseInterface stubs
     */
    public function getProtocolVersion()
    {
        return '1.1';
    }

    public function withProtocolVersion($version)
    {
        return $this;
    }

    public function getHeaders()
    {
        return [];
    }

    public function hasHeader($name)
    {
        return false;
    }

    public function getHeader($name)
    {
        return null;
    }

    public function getHeaderLine($name)
    {
        return null;
    }

    public function withHeader($name, $value)
    {
        return $this;
    }

    public function withAddedHeader($name, $value)
    {
        return $this;
    }

    public function withoutHeader($name)
    {
        return $this;
    }

    public function getBody()
    {
        return new BufferedStream();
    }

    public function withBody(StreamInterface $body)
    {
        return $this;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        return $this;
    }

    public function getReasonPhrase()
    {
        return '';
    }

}
