<?php

require_once "Query.php";
require_once "Request.php";
require_once "Response.php";
require_once "../api/base.api.php";
require_once "../services/function.service.php";

class HTTPServer
{
    private $server;
    public function __construct()
    {
        $this->server = new Swoole\HTTP\Server(serverHost, httpPort);

        $this->onStart();
        $this->onMessage();
        $this->server->start();
    }

    public function onStart()
    {
        $this->server->on("start", function (Swoole\Http\Server $server) {

            echo Message::httpRuning . httpPort . PHP_EOL;
        });
    }
    public function onMessage()
    {
        $this->server->on("request", function (Swoole\Http\Request $req, Swoole\Http\Response $res) {

            $msg = $req->rawContent();

            echo Message::onMessage . PHP_EOL . $msg . PHP_EOL . PHP_EOL;

            $jsonArray = json_decode($msg, true);
            $request =  new Request($jsonArray);

            $result = json_encode(BaseApi::checkObject($request));

            $res->end($result);

            echo Message::reply   . PHP_EOL . $result . PHP_EOL . PHP_EOL;
        });
    }
}
new HTTPServer();
