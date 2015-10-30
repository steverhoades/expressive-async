<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/27/15
 * Time: 11:16 PM
 */

namespace ExpressiveAsync;

use Psr\Http\Message\StreamInterface;

/**
 * Provides a buffer stream that can be written to to fill a buffer, and read
 * from to remove bytes from the buffer.
 *
 * This stream returns a "hwm" metadata value that tells upstream consumers
 * what the configured high water mark of the stream is, or the maximum
 * preferred size of the buffer.
 */
class BufferedStream implements StreamInterface
{
    private $buffer = '';

    private $position = 0;

    public function __toString()
    {
        return $this->getContents();
    }

    public function getContents()
    {
        $buffer = $this->buffer;

        return $buffer;
    }

    public function close()
    {
        $this->buffer = '';
    }

    public function detach()
    {
        $this->close();
    }

    public function getSize()
    {
        return strlen($this->buffer);
    }

    public function isReadable()
    {
        return true;
    }

    public function isWritable()
    {
        return true;
    }

    public function isSeekable()
    {
        return false;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException('Cannot seek a BufferStream');
    }

    public function eof()
    {
        return strlen($this->buffer) === 0;
    }

    public function tell()
    {
        throw new \RuntimeException('Cannot determine the position of a BufferStream');
    }

    /**
     * Reads data from the buffer.
     */
    public function read($length)
    {
        $currentLength = strlen($this->buffer);

        if ($length >= $currentLength) {
            // No need to slice the buffer because we don't have enough data.
            $result = $this->buffer;
            $this->position = strlen($this->buffer);
        } else {
            // Slice up the result to provide a subset of the buffer.
            $result = substr($this->buffer, $this->position, $length);
            $this->position = $this->position + $length;
        }

        return $result;
    }

    /**
     * Writes data to the buffer.
     */
    public function write($string)
    {
        $this->buffer .= $string;

        return strlen($string);
    }

    public function getMetadata($key = null)
    {
        return $key ? null : [];
    }
}
