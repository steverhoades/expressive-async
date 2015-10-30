<?php
namespace ExpressiveAsync\Emitter;

use Zend\Diactoros\Response\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

class AsyncEmitter implements EmitterInterface
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Emits a response for a PHP SAPI environment.
     *
     * Emits the status line and headers via the header() function, and the
     * body content via the output buffer.
     *
     * @param ResponseInterface $response
     * @param null|int $maxBufferLevel Maximum output buffering level to unwrap.
     */
    public function emit(ResponseInterface $response, $maxBufferLevel = null)
    {
        echo get_class($this->conn) . PHP_EOL;
        $this->emitStatusLine($response);
        $this->emitHeaders($response);
        $this->emitBody($response, $maxBufferLevel);
        $this->conn->end();
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is availble, it, too, is emitted.
     *
     * @param ResponseInterface $response
     */
    private function emitStatusLine(ResponseInterface $response)
    {
        $reasonPhrase = $response->getReasonPhrase();
        $header = sprintf(
            "HTTP/%s %d%s\r\n",
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($reasonPhrase ? ' ' . $reasonPhrase : '')
        );
        $this->conn->write($header);
        echo $header;
    }

    /**
     * Emit response headers.
     *
     * Loops through each header, emitting each; if the header value
     * is an array with multiple values, ensures that each is sent
     * in such a way as to create aggregate headers (instead of replace
     * the previous).
     *
     * @param ResponseInterface $response
     */
    private function emitHeaders(ResponseInterface $response)
    {
        foreach ($response->getHeaders() as $header => $values) {
            $name  = $this->filterHeader($header);
            foreach ($values as $value) {
                $header = sprintf(
                    "%s: %s\r\n",
                    $name,
                    $value
                );
                $this->conn->write($header);
                echo $header;
            }
        }
    }

    /**
     * Emit the message body.
     *
     * Loops through the output buffer, flushing each, before emitting
     * the response body using `echo()`.
     *
     * @param ResponseInterface $response
     * @param int $maxBufferLevel Flush up to this buffer level.
     */
    private function emitBody(ResponseInterface $response, $maxBufferLevel)
    {
        echo $response->getBody() .": ksksk";
        $this->conn->write("\r\n");
        $this->conn->write($response->getBody());
    }

    /**
     * Filter a header name to wordcase
     *
     * @param string $header
     * @return string
     */
    private function filterHeader($header)
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);
        return str_replace(' ', '-', $filtered);
    }
}
