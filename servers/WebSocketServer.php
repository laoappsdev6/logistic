<?php

require_once "Request.php";
require_once "Response.php";
require_once "../api/base.api.php";
require_once "../services/function.service.php";

class WebSocketServer
{
    private $socket;

    public function __construct()
    {
        $this->socket = new Swoole\Websocket\Server(serverHost, webSocketPort);

        $this->onStart();
        $this->onOpen();
        $this->onMessage();
        $this->onClose();
        $this->socket->start();
    }
    public function onStart()
    {
        $this->socket->on("start", function ($server) {

            echo Message::socketRuning . webSocketPort . PHP_EOL;
        });
    }
    public function onOpen()
    {
        $this->socket->on('open', function ($server, $req) {

            echo Message::clientConnect . $req->fd . PHP_EOL;
        });
    }
    public function onMessage()
    {
        $this->socket->on('message', function ($server, $frame) {

            $client = $frame->fd;
            $msg = $frame->data;

            echo Message::onMessage . $client . PHP_EOL . $msg . PHP_EOL;

            $jsonArray = json_decode($msg, true);
            $request =  new Request($jsonArray);

            $result = json_encode(BaseApi::checkObject($request));

            $server->push($client, $result);

            echo Message::reply . $client . PHP_EOL . $result . PHP_EOL;
        });
    }
    public function onClose()
    {
        $this->socket->on('close', function ($server, $fd) {

            echo Message::clientClose . $fd . PHP_EOL;
        });
    }
}
new WebSocketServer();
