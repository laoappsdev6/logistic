<?php

class Request
{
    public string $object = "";
    public string $method = "";
    public array $data = [];
    public string $token = "";

    public function __construct(array $jsonArray = [])
    {
        foreach ($jsonArray as $property => $value) {
            if (property_exists('Request', $property)) {
                $this->$property = $value;
            }
        }
        $_SESSION[Name::obj] = $this->object;
        $_SESSION[Name::method] = $this->method;
    }
}
