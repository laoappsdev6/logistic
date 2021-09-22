<?php

require_once "validate.model.php";
require_once "../controllers/validate.controller.php";

class LoginModel
{
    public string $username;
    public string $password;

    public function __construct(array $object)
    {
        if ($object) {
            foreach ($object as $property => $value) {
                if (property_exists('LoginModel', $property)) {
                    $this->$property = $value;
                }
            }
        }
    }

    public function validateAll(): array
    {
        if (empty($this->username) && empty($this->password)) {
            return jsonValidate(Message::emptyUserAndPass);
        }
        if (empty($this->username)) {
            return jsonValidate(Message::userEmpty);
        }
        if (empty($this->password)) {
            return jsonValidate(Message::passEmpty);
        }
        return [];
    }
}
