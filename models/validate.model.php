<?php

function validateEmptyObject($data): array
{
    if (!$data) return JsonValidate(Message::objEmpty);
    return [];
}

function validateEmpty($data, string $key): array
{
    if (empty($data)) return JsonValidate($key . Message::empty);
    return [];
}

function validateStringLenght($data, int $number, string $key): array
{
    if (strlen($data) < $number) return JsonValidate($key . Message::mustBeThan . $number);
    return [];
}

function validateIsNumber($data, string $key): array
{
    if (!is_numeric($data)) return JsonValidate($key . Message::number);
    return [];
}

function validateData($data, string $key): array
{
    $date = DateTime::createFromFormat(datefmt, $data);
    if (!$date) return JsonValidate($key . Message::date);
    return [];
}

function validateTime($data, string $key): array
{
    $time = DateTime::createFromFormat(timefmt, $data);
    if (!$time) return JsonValidate($key . Message::time);
    return [];
}

function validateDataTime($data, string $key): array
{
    $dateTime = DateTime::createFromFormat(dateTimefmt, $data);
    if (!$dateTime)  return JsonValidate($key . Message::dateTime);
    return [];
}

function validateNotEqual(int $start, int $end): array
{
    if ($start == $end)  return JsonValidate($start . Message::notEqual . $end);
    return [];
}
