<?php
namespace ExpressiveAsync;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use React\Socket\ConnectionInterface;

/**
 * PSR-7 request implementation.
 */
class ServerRequest implements ServerRequestInterface, AsyncMessageInterface
{
    use AsyncMessageTrait;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $cookieParams = [];

    /**
     * @var null|array|object
     */
    private $parsedBody;

    /**
     * @var array
     */
    private $queryParams = [];

    /**
     * @var array
     */
    private $serverParams;

    /**
     * @var array
     */
    private $uploadedFiles;

    /**
     * @var string
     */
    private $remoteAddress;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($attribute, $default = null)
    {
        if (! array_key_exists($attribute, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($attribute, $value)
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($attribute)
    {
        if (! isset($this->attributes[$attribute])) {
            return clone $this;
        }

        $new = clone $this;
        unset($new->attributes[$attribute]);
        return $new;
    }

    /**
     * Proxy to receive the request method.
     *
     * This overrides the parent functionality to ensure the method is never
     * empty; if no method is present, it returns 'GET'.
     *
     * @return string
     */
    public function getMethod()
    {
        $method = $this->request->getMethod();
        if (empty($method)) {
            return 'GET';
        }
        return $method;
    }

    /**
     * @return string
     */
    public function getRequestTarget()
    {
        return $this->request->getRequestTarget();
    }

    /**
     * @param mixed $requestTarget
     * @return RequestInterface
     */
    public function withRequestTarget($requestTarget)
    {
        $this->request =  $this->request->withRequestTarget($requestTarget);

        return $this;
    }

    /**
     * @param string $method
     * @return RequestInterface
     */
    public function withMethod($method)
    {
        $this->request =  $this->request->withMethod($method);

        return $this;
    }

    /**
     * @return UriInterface
     */
    public function getUri()
    {
        return $this->request->getUri();
    }

    /**
     * @param UriInterface $uri
     * @param bool|false $preserveHost
     * @return RequestInterface
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $this->request = $this->request->withUri($uri, $preserveHost);

        return $this;
    }

    /**
     * @param string $header
     * @param string|\string[] $value
     * @return \Psr\Http\Message\MessageInterface
     */
    public function withHeader($header, $value)
    {
        $this->request = $this->request->withHeader($header, $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->request->getProtocolVersion();
    }

    /**
     * @param string $version
     * @return \Psr\Http\Message\MessageInterface
     */
    public function withProtocolVersion($version)
    {
        $this->request = $this->withProtocolVersion($version);

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->request->getHeaders();
    }

    /**
     * @param string $header
     * @return bool
     */
    public function hasHeader($header)
    {
        return $this->request->hasHeader($header);
    }

    /**
     * @param string $header
     * @return \string[]
     */
    public function getHeader($header)
    {
       return $this->getHeader($header);
    }

    /**
     * @param string $header
     * @return string
     */
    public function getHeaderLine($header)
    {
        return $this->request->getHeaderLine($header);
    }

    /**
     * @param string $header
     * @param string|\string[] $value
     * @return \Psr\Http\Message\MessageInterface
     */
    public function withAddedHeader($header, $value)
    {
        $this->request = $this->request->withAddedHeader($header, $value);

        return $this;
    }

    /**
     * @param string $header
     * @return \Psr\Http\Message\MessageInterface
     */
    public function withoutHeader($header)
    {
        $this->request =  $this->request->withoutHeader($header);

        return $this;
  }



    /**
     * Get the remote address
     *
     * @return string
     */
    public function getRemoteAddress()
    {
        if (!is_null($this->connection)) {
           return $this->connection->getRemoteAddress();
        }

        return $this->remoteAddress;
    }

    /**
     * @param $address
     * @return ServerRequest
     */
    public function withRemoteAddress($address)
    {
        $new = clone $this;
        $new->remoteAddress = $address;

        return $new;
    }

    /**
     * @return Stream
     */
    public function getBody()
    {
        return $this->request->getBody();
    }

    /**
     * @param StreamInterface $body
     * @return $this|Request
     */
    public function withBody(StreamInterface $body)
    {
        $this->request = $this->request->withBody($body);

        return $this;
    }

}
