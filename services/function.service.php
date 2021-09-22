<?php

require_once "TEXT.php";
require_once "jwt.service.php";
require_once "name.service.php";
require_once "method.service.php";
require_once "object.service.php";
require_once "message.service.php";
require_once "../config/config.php";
require_once "../controllers/databasePDO.controller.php";


function getRes(array $data, string $message, int $status): Response
{
    $res = new Response();
    $res->object = $_SESSION[Name::obj];
    $res->method = $_SESSION[Name::method];
    $res->data = $data;
    $res->message = $message;
    $res->status = $status;
    return $res;
}
function JsonValidate(string $message): array
{
    return array(Name::message => $message);
}

function Pagination(int $numRow, array $data, int $limit,  int $page): array
{
    $allPage = ceil($numRow / $limit);
    return array(Name::data => $data, Name::page => $page, Name::pageTotal => $allPage, Name::dataTotal => $numRow);
}

function JwtEncode(array $dataUser): array
{
    return array(Name::token => registerToken($dataUser[0]), Name::data => $dataUser[0]);
}

function ValidateJwt(string $token): bool
{
    if (isset($token) && !empty($token)) {
        $userId = checkToken($token);
        if ($userId) {
            $_SESSION[Name::userId] = $userId;
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function base64_to_jpeg(string $base64_string, string $output_file): string
{
    $ifp = fopen($output_file, "wb");
    fwrite($ifp, base64_decode($base64_string));
    fclose($ifp);
    return $output_file;
}

function dateTime(): string
{
    date_default_timezone_set(timeZone);
    return date(dateTimefmt);
}

function formatDate(string $date): string
{
    date_default_timezone_set(timeZone);
    $date = new DateTime($date);
    return $date->format(dateTimefmt);
}

function myFilter($var)
{
    return $var !== null && $var !== "" && !empty($var);
}

function Filter(array $array): array
{
    return array_filter($array, "myFilter");
}

function toServerTime($time, $timezone)
{
    return date('Y-m-d H:i:s', $time + ($GLOBALS['SERVER_TIMEZONE'] - $timezone) * 3600);
}
function getDeviceStatus($status)
{
    $TEXT = $GLOBALS['TEXT'];
    for ($i = 0; $i < intval(strlen($status) / 4); $i++) {
        $state = strtoupper(substr($status, $i * 4, 4));
        $txt = $TEXT[$state];
        if (isset($txt)) {
            $output[] = $txt;
        }
    }
    if (isset($output)) {
        return implode(',', $output);
    } else {
        return '';
    }
}

function getDeviceIoParam($params, $ios, $online, $command)
{
    $timezone = isset($_SESSION['timezone']) ? (float) $_SESSION['timezone'] : 0;
    $unit_speed = isset($_SESSION['unit_speed']) ? $_SESSION['unit_speed'] : 0;
    $unit_dist = isset($_SESSION['unit_distance']) ? $_SESSION['unit_distance'] : 0;
    $unit_fuel = isset($_SESSION['unit_fuel']) ? $_SESSION['unit_fuel'] : 0;
    $unit_temp = isset($_SESSION['unit_temperature']) ? $_SESSION['unit_temperature'] : 0;
    $datetime_fmt = isset($_SESSION['datetime_fmt']) ? $_SESSION['datetime_fmt'] : 0;
    $unit_altitude = isset($_SESSION['unit_altitude']) ? $_SESSION['unit_altitude'] : 0;

    $array = explode(',', $ios);
    if ($array != null) {
        foreach ($array as $item) {
            if ($item != '') {
                $ret = explode(':', $item);
                $id = hexdec('0x' . $ret[0]);

                $func = $params[$id]['attfunc'];
                //if($func=='GEO' || $func=='MAT' || $func=='NGEO' || $func=='NPOI' || $func=='IGEO' || $func =='ENGH'){
                $value = $ret[1];
                //}else{
                //	$value = (int)$ret[1];
                //}

                if ($func == 'DIV10') {
                    $value = $value / 10;
                }

                /*里程单位转换*/
                if ($unit_dist == 1 && ($id == 10 || $id == 63)) {
                    //Mile(英里)
                    $value = $value * 0.6213712;
                    if ($params[$id]['vformat'] != '') {
                        $params[$id]['vformat'] = $params[$id]['vformat'] . 'mi';
                    }
                } else if ($unit_dist == 2 && ($id == 10 || $id == 63)) {
                    //Nautical mile(海里)
                    $value = $value * 0.5399568;
                    if ($params[$id]['vformat'] != '') {
                        $params[$id]['vformat'] = $params[$id]['vformat'] . 'nmi';
                    }
                } else if ($unit_dist == 0 && ($id == 10 || $id == 63)) {
                    //Kilometer(公里)
                    $params[$id]['vformat'] = $params[$id]['vformat'] . 'km';
                }

                if ($unit_dist == 1 && ($id == 14 || $id == 15)) {
                    //Mile(英里)
                    $dist_index = strripos($value, " ") + 1;
                    $dist_len = strlen(substr($value, $dist_index)) - 2;
                    $dist = round((float)substr($value, $dist_index, $dist_len) * 0.6213712, 1);
                    $value = substr($value, 0, $dist_index) . '(' . $dist . ' mi)';
                } else if ($unit_dist == 2 && ($id == 14 || $id == 15)) {
                    //Nautical mile(海里)
                    $dist_index = strripos($value, " ") + 1;
                    $dist_len = strlen(substr($value, $dist_index)) - 2;
                    $dist = round((float)substr($value, $dist_index, $dist_len) * 0.5399568, 1);
                    $value = substr($value, 0, $dist_index) . '(' . $dist . ' nmi)';
                } else if ($unit_dist == 0 && ($id == 14 || $id == 15)) {
                    //Kilometer(公里)
                    $dist_index = strripos($value, " ") + 1;
                    $dist_len = strlen(substr($value, $dist_index)) - 2;
                    $dist = round((float)substr($value, $dist_index, $dist_len), 1);
                    $value = substr($value, 0, $dist_index) . '(' . $dist . ' km)';
                }

                if ($unit_speed == 1 && $id == 60) {
                    //mph(英里/小时)
                    $value = round($value * 0.6213712, 0);
                    if ($params[$id]['vformat'] != '') {
                        $params[$id]['vformat'] = $params[$id]['vformat'] . ' mph';
                    }
                } else if ($unit_speed == 0 && $id == 60) {
                    $value = $value;
                    if ($params[$id]['vformat'] != '') {
                        $params[$id]['vformat'] = $params[$id]['vformat'] . ' kph';
                    }
                }

                if ($unit_altitude == 1 && $id == 27) {
                    //ft(英尺)
                    $value = round($value * 3.28083989501, 0);
                    if ($params[$id]['vformat'] != '') {
                        $params[$id]['vformat'] = $params[$id]['vformat'] . ' ft';
                    }
                } else if ($unit_altitude == 0 && $id == 27) {
                    $value = $value;
                    if ($params[$id]['vformat'] != '') {
                        $params[$id]['vformat'] = $params[$id]['vformat'] . ' m';
                    }
                }

                /*油量单位转换*/
                if ($unit_fuel == 1 && ($id == 30 || $id == 31 || $id == 80)) {
                    //Gallon(加仑)
                    $value = round($value * 0.2199692);
                    if ($params[$id]['vformat'] != '') {
                        $params[$id]['vformat'] = $params[$id]['vformat'] . 'gal';
                    }
                } else if ($unit_fuel == 0 && ($id == 30 || $id == 31 || $id == 80)) {
                    if ($params[$id]['vformat'] != '') {
                        $params[$id]['vformat'] = $params[$id]['vformat'] . 'L';
                    }
                }

                /*温度单位转换*/
                if ($unit_temp == 1 && ($id == 72 || $id == 73)) {
                    //Fahrenheit
                    $value = $value * 1.8 + 32;
                    if ($params[$id]['vformat'] != '') {
                        $params[$id]['vformat'] = $params[$id]['vformat'] . '℉';
                    }
                } else if ($unit_temp == 0 && ($id == 72 || $id == 73)) {
                    if ($params[$id]['vformat'] != '') {
                        $params[$id]['vformat'] = $params[$id]['vformat'] . '℃';
                    }
                }

                $attrib = $params[$id]['attrib'] . ': ';
                if ($params[$id]['vformat'] != '') {
                    $output[] = $attrib . sprintf($params[$id]['vformat'], $value);
                } else if ($func == 'DHMS' /*&& $online == 1*/) {
                    $output[] = $attrib . secondsToWords((int)$value);
                } else if ($func == 'LENG') {
                    $output[] = $attrib . toCustomTime(new DateTime(date('Y-m-d H:i:s', (int)$value)), $timezone, $datetime_fmt);
                } else if ($func == 'CMID') {
                    $output[] = $attrib . $command[(int)$value];
                } else if ($params[$id]['voption'] != '') {
                    $subs = explode(';', $params[$id]['voption']);
                    foreach ($subs as $item) {
                        $ret = explode('=', $item);
                        if ($value == (int)$ret[0]) {
                            $output[] = $attrib . $value . '(' . $ret[1] . ')';
                            break;
                        } else {
                            $vls = explode('..', $ret[0]);
                            if (count($vls) == 2 && $value >= (int)$vls[0] && $value <= (int)$vls[1]) {
                                $output[] = $attrib . $value . '(' . $ret[1] . ')';
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    if (isset($output)) {
        return implode("<br> ", $output);
    } else {
        return '';
    }
}

function secondsToWords($seconds)
{
    $TEXT = $GLOBALS['TEXT'];
    $ret = "";

    /*** get the days ***/
    $days = intval(intval($seconds) / (3600 * 24));
    if ($days > 0) {
        $ret .= "$days " . $TEXT['js-dhms-day'];
    }

    /*** get the hours ***/
    $hours = (intval($seconds) / 3600) % 24;
    if ($hours > 0) {
        $ret .= " $hours " . $TEXT['js-dhms-hour'];
    }

    /*** get the minutes ***/
    $minutes = (intval($seconds) / 60) % 60;
    if ($minutes > 0) {
        $ret .= " $minutes " . $TEXT['js-dhms-min'];
    }

    /*** get the seconds ***/
    $seconds = intval($seconds) % 60;
    if ($seconds > 0) {
        $ret .= " $seconds " . $TEXT['js-dhms-second'];
    }

    return $ret;
}

function toCustomTime($time, $timezone, $datetimefmt)
{
    if ($time != null) {
        $endtime = $time->getTimestamp() - ($GLOBALS['SERVER_TIMEZONE'] - $timezone) * 3600;
        return date('Y-m-d H:i:s', $endtime);
        /*客户端自己转换时间格式
		if ($datetimefmt) {
            return date($datetimefmt, $endtime);
        } else {
            return date('Y-m-d H:i:s', $endtime);
        }
		*/
    } else {
        return '';
    }
}
