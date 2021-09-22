<?php

require_once "../models/user.model.php";
require_once "../controllers/user.controller.php";

class UserApi
{
    public static function checkMethod(Request $obj): Response
    {
        if (!$obj->data && $obj->method !== UsersMethod::listAll) return getRes([], Message::dataEmpty, Status::fail);

        $dataModel = new UserModel($obj->data);

        switch ($obj->method) {

            case UsersMethod::add:
                $validateAll = $dataModel->validateAll();
                if (count($validateAll)) return getRes($validateAll, Message::validationError, Status::fail);
                return UserController::add($dataModel);

            case UsersMethod::update:
                $validateId = $dataModel->checkId();
                if (count($validateId)) return getRes($validateId, Message::validationError, Status::fail);
                $validateAll = $dataModel->validateAll();
                if (count($validateAll)) return getRes($validateAll, Message::validationError, Status::fail);
                return UserController::update($dataModel);

            case UsersMethod::delete:
                $validateId = $dataModel->checkId();
                if (count($validateId)) return getRes($validateId, Message::validationError, Status::fail);
                return UserController::delete($dataModel);

            case UsersMethod::listOne:
                return UserController::listOne($dataModel);

            case UsersMethod::listPage:
                return UserController::listPage($dataModel);

            case UsersMethod::listAll:
                return UserController::listAll();

            default:
                return getRes([], Message::methodNotFound, Status::fail);
        }
    }
}
