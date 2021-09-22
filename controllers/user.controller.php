<?php

class UserController
{
    public static function add(Usermodel $obj): Response
    {
        $sql = "insert into users (name,username,password,phonenumber,remark) 
                values ('$obj->name','$obj->username','$obj->password','$obj->phoneNumber','$obj->remark')";
        return PDODBController::insert($sql);
    }
    public static function update(Usermodel $obj): Response
    {
        $sql = "update users set name='$obj->name', username='$obj->username',password='$obj->password',
                phonenumber='$obj->phoneNumber', remark='$obj->remark' where id='$obj->id'";
        return PDODBController::update($sql);
    }
    public static function delete(Usermodel $obj): Response
    {
        $sql = "delete from users where id='$obj->id'";
        return PDODBController::delete($sql);
    }

    public static function listOne(Usermodel $obj): Response
    {
        $sql = "select * from users where id='$obj->id'";
        return PDODBController::selectOne($sql);
    }

    public static function listPage(Usermodel $obj): Response
    {
        $sqlCount = "select count(*) as num from users ";

        $sqlPage = "select * from users ";

        if (isset($obj->keyword) && !empty($obj->keyword)) {
            $sqlSearch = " where
                        name like '%$obj->keyword%' or
                        username like '%$obj->keyword%'
                        ";
            $sqlCount .= $sqlSearch;
            $sqlPage .= $sqlSearch;
        }

        $orderBy = "id";

        return PDODBController::selectPage($obj, $sqlCount, $sqlPage, $orderBy);
    }

    public static function listAll(): Response
    {
        $sql = "select * from users order by id desc ";
        return PDODBController::selectAll($sql);
    }
}
