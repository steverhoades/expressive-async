<?php

namespace ExpressiveAsync;

use Evenement\EventEmitter;
use GuzzleHttp\Psr7 as gPsr;
use React\EventLoop\LoopInterface;
use ExpressiveAsync\ServerRequest;
use React\Http\MultipartParser;

/**
 * @event headers
 * @event error
 */
class RequestParser extends EventEmitter
{
    private $buffer = '';
    private $maxSize = 4096;

    /**
     * @var Request
     */
    private $request;
    private $length = 0;

    public function feed($data)
    {
        $this->buffer .= $data;

        if (!$this->request && false !== strpos($this->buffer, "\r\n\r\n")) {

            // Extract the header from the buffer
            // in case the content isn't complete
            list($headers, $this->buffer) = explode("\r\n\r\n", $this->buffer, 2);

            // Fail before parsing if the
            if (strlen($headers) > $this->maxSize) {
                $this->headerSizeExceeded();
                return;
            }

            $this->request = gPsr\parse_request($headers . "\r\n\r\n");
        }

        // if there is a request (meaning the headers are parsed) and
        // we have the right content size, we can finish the parsing
        if ($this->request && $this->isRequestComplete()) {
            $body = substr($this->buffer, 0, $this->length);
            // create a stream for the body.
            $stream = new BufferedStream();
            $stream->write($body);
            // add stream to the request.
            $this->request = $this->request->withBody($stream);

            // create server request object
            $this->request = new ServerRequest($this->request);

            // todo this should really belong in the header parsing.  clean this up.
            $parsedQuery = [];
            $queryString = $this->request->getUri()->getQuery();
            if ($queryString) {
                parse_str($queryString, $parsedQuery);
                if (!empty($parsedQuery)) {
                    $this->request = $this->request->withQueryParams($parsedQuery);
                }
            }

            // add server request information to the request object.
            $this->request = $this->parseBody($body, $this->request);

            $this->emit('headers', array($this->request));
            $this->removeAllListeners();
            $this->request = null;
            return;
        }

        // fail if the header hasn't finished but it is already too large
        if (!$this->request && strlen($this->buffer) > $this->maxSize) {
            $this->headerSizeExceeded();
            return;
        }
    }

    public function parseBody($content, $request)
    {
        $headers = $request->getHeaders();

        if (array_key_exists('Content-Type', $headers)) {
            if (strpos($headers['Content-Type'][0], 'multipart/') === 0) {
                //TODO :: parse the content while it is streaming
                preg_match("/boundary=\"?(.*)\"?$/", $headers['Content-Type'][0], $matches);
                $boundary = $matches[1];

                $parser = new MultipartParser($content, $boundary);
                $parser->parse();

                $request = $request->withParsedBody($parser->getPost());
                $request = $request->withUploadedFiles($parser->getFiles());
            } else if (strtolower($headers['Content-Type'][0]) == 'application/x-www-form-urlencoded') {
                parse_str(urldecode($content), $result);
                $request = $request->withParsedBody($result);
            }
        }

        return $request;
    }

    protected function isRequestComplete()
    {
        $contentLength = $this->request->getHeader('Content-Length');

        // if there is no content length, there should
        // be no content so we can say it's done
        if (!$contentLength) {
            return true;
        }

        // if the content is present and has the
        // right length, we're good to go
        if ($contentLength[0]  && strlen($this->buffer) >= $contentLength[0] ) {

            // store the expected content length
            $this->length = $contentLength[0];

            return true;
        }

        return false;
    }

    protected function headerSizeExceeded()
    {
        $this->emit('error', array(new \OverflowException("Maximum header size of {$this->maxSize} exceeded."), $this));
    }
}
