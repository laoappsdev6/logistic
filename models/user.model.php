<?php

require_once "validate.model.php";
require_once "../controllers/validate.controller.php";

class UserModel
{
    public int $id = 0;
    public string $name;
    public string $username;
    public string $password;
    public string $phoneNumber;
    public string $remark;

    public int $page = 1;
    public int $limit = 10;
    public string $keyword;

    public function __construct(array $object)
    {
        if ($object) {
            foreach ($object as $property => $value) {
                if (property_exists('UserModel', $property)) {
                    $this->$property = $value;
                }
            }
        }
    }

    public function validateAll(): array
    {
        $result = array();
        foreach ($this as $key => $value) {
            $check = $this->validate($key, $value);
            if (!is_null($check) && !empty($check)) {
                $result = array_merge($result, $check);
            }
        }
        return $result;
    }

    public function validate($key, $value): array
    {
        $msg = [];
        switch ($key) {
            case Name::name:
                $msg[] = validateEmpty($value, $key);
                break;
            case Name::username:
                $msg[] = validateEmpty($value, $key);
                $sql = "select * from users where username='$value' and id !='$this->id'";
                $msg[] = validateAlreadyExist($sql, $key, $value);
                break;
            case Name::password:
                $msg[] = validateEmpty($value, $key);
                break;
            case Name::phoneNumber:
                $msg[] = validateEmpty($value, $key);
                break;
        }
        return Filter($msg);
    }

    public function checkId(): array
    {
        $sql = "select * from users where id='$this->id'";
        return validateNotAvailable($sql, Name::userId, $this->id);
    }

    public function validateNewPassword(): array
    {
        return validateEmpty($this->newPassword, "New password");
    }
}
