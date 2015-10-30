<?php

namespace ExpressiveAsync\Test;

use ExpressiveAsync\Server;;

class ServerTest extends TestCase
{
    public function testRequestEventIsEmitted()
    {
        $io = new ServerStub();

        $server = new Server($io);
        $server->on('request', $this->expectCallableOnce());

        $conn = new ConnectionStub();
        $io->emit('connection', array($conn));

        $data = $this->createGetRequest();
        $conn->emit('data', array($data));
    }

    public function testRequestEvent()
    {
        $io = new ServerStub();

        $i = 0;

        $server = new Server($io);
        $server->on('request', function ($conn, $request, $response) use (&$i) {
            $i++;

            $this->assertInstanceOf('ExpressiveAsync\Test\ConnectionStub', $conn);
            $this->assertInstanceOf('ExpressiveAsync\ServerRequest', $request);
            $this->assertSame('/', $request->getUri()->getPath());
            $this->assertSame('GET', $request->getMethod());
            $this->assertSame('127.0.0.1', $request->getRemoteAddress());

            $this->assertInstanceOf('ExpressiveAsync\Response', $response);
        });

        $conn = new ConnectionStub();
        $io->emit('connection', array($conn));

        $data = $this->createGetRequest();
        $conn->emit('data', array($data));

        $this->assertSame(1, $i);
    }

    private function createPostRequest()
    {
        $data = "GET / HTTP/1.1\r\n";
        $data .= "Host: example.com:80\r\n";
        $data .= "Connection: close\r\n";
        $data .= "\r\n";

        return $data;
    }

    private function createGetRequest()
    {
        $data = "GET / HTTP/1.1\r\n";
        $data .= "Host: example.com:80\r\n";
        $data .= "Connection: close\r\n";
        $data .= "\r\n";

        return $data;
    }
}
