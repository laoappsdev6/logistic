<?php

require_once "../config/config.php";

class QueryClient
{
    public static function connection()
    {
        $client = new Swoole\Client(SWOOLE_SOCK_TCP);

        if (!$client->connect(queryServerHost, queryServerPort, queryWait)) {
            echo Message::queryConnectFail . $client->errCode . PHP_EOL;
            return null;
        }
        return $client;
    }
    public static function query(String $query): array
    {
        $client = QueryClient::connection();
        $query = str_replace('\n', '', $query);
        $command = array("command" => "query", "data" => [$query]);
        $result = $client->send(json_encode($command));

        $msg = "";

        while ($result) {
            try {
                $data = @$client->recv();
                if (empty($data)) {
                    $client->close();
                    $json = json_decode($msg, true);
                    return @$json['data'][0];
                } else {
                    $msg .= $data;
                }
            } catch (Throwable $th) {
                return [];
                print_r($th);
            }
        }
        return [];
    }
}

// $result = QueryClient::query("select * from cfg_customer");
// echo json_encode($result);
