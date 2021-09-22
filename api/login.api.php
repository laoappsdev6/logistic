<?php

require_once "../models/login.model.php";
require_once "../controllers/authorize.controller.php";

class LoginApi
{
    public static function checkMethod(Request $obj): Response
    {
        if (!$obj->data) return getRes([], Message::dataEmpty, Status::fail);

        $objModel = new LoginModel($obj->data);

        switch ($obj->method) {

            case LoginMethod::authorize:
                $validateAll = $objModel->validateAll();
                if (count($validateAll)) return getRes($validateAll, Message::validationError, Status::fail);
                return LoginController::authorize($objModel);
            default:
                return getRes([], Message::methodNotFound, Status::fail);
        }
    }
}
