<?php

require_once "login.api.php";
require_once "case1.TripLogReport.api.php";

class BaseApi
{
    public static function checkObject(Request $obj): Response
    {
        // if ($obj->object !== Objects::login)
        //     if (!ValidateJwt($obj->token)) return getRes([], Message::noAuthorize, Status::fail);

        switch ($obj->object) {

            case Objects::case1:
                return Case1TripLogReportApi::checkMethod($obj);
            case Objects::login:
                return LoginApi::checkMethod($obj);
            default:
                return getRes([], Message::objectNotFound, Status::fail);
        }
    }
}
