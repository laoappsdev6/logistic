<?php

require_once "Request.php";
require_once "Response.php";
require_once "../api/base.api.php";
require_once "../services/function.service.php";

class TCPSocketServer
{
    private $socket;

    public function __construct()
    {
        $this->socket = new Swoole\Server(serverHost, tcpPort);

        $this->onStart();
        $this->onConnect();
        $this->onMessage();
        $this->onClose();
        $this->socket->start();
    }
    public function onStart()
    {
        $this->socket->on("start", function ($server) {

            echo Message::tcpRuning . tcpPort . PHP_EOL;
        });
    }
    public function onConnect()
    {
        $this->socket->on('connect', function ($server, $fd) {

            echo Message::clientConnect . $fd . PHP_EOL;
        });
    }
    public function onMessage()
    {
        $this->socket->on('receive', function ($server, $fd, $from_id, $data) {

            $client = $fd;
            $msg = $data;

            echo Message::onMessage . $client . PHP_EOL . $msg . PHP_EOL;

            $jsonArray = json_decode($msg, true);
            $request =  new Request($jsonArray);

            $result = json_encode(BaseApi::checkObject($request));

            $server->send($client, $result);

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
new TCPSocketServer();
