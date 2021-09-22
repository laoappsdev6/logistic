<?php

require_once "../controllers/case1.TripLogReport.controller.php";

class Case1TripLogReportApi
{
    public static function checkMethod(Request $obj): Response
    {
        if (!$obj->data) return getRes([], Message::dataEmpty, Status::fail);

        $data = (object) $obj->data;

        switch ($obj->method) {

            case reportMethod::report:
                return Case1TripLogReportController::reportList($data);
            default:
                return getRes([], Message::methodNotFound, Status::fail);
        }
    }
}
