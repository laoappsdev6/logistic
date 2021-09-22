<?php

define("dbhost", "localhost");
define("dbuser", "root");
define("dbpass", "");
define("dbname", "swoole");
define("dbcharset", "utf8");
define("dbsetname", "SET NAMES utf8");

define("serverHost", "0.0.0.0");
define("webSocketPort", 4200);
define("httpPort", 14568);
define("tcpPort", 4400);

// define("queryServerHost", "192.168.1.137");
// define("queryServerPort", 28000);
//185.2.102.147
//laotracking.com
define("queryServerHost", "185.2.102.147");
define("queryServerPort", 33589);
define("queryWait", 1);

define("imagePath", "../image/");
define("timeZone", "Asia/Bangkok");
define("dateTimefmt", "Y-m-d H:i:s");
define("datefmt", "Y-m-d");
define("timefmt", "H:i:s");

$GLOBAL_DOWNLOAD_MAX_POINTS = 20000; //下载历史记录和报表最大点数
$tz = timezone_open(date_default_timezone_get());
$dateTimeGMT = date_create("now", timezone_open("Etc/GMT"));
$SERVER_TIMEZONE = timezone_offset_get($tz, $dateTimeGMT) / 3600;
$_SESSION['timezone'] = 7;
$_SESSION['unit_speed'] = "kph";
