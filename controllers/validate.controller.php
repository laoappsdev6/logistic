<?php

function validateAlreadyExist(string $sql, string $key, $value): array
{
    $name = PDODBController::query($sql);
    if ($name) return JsonValidate($key . ": " . $value . Message::already);
    return [];
}
function validateNotAvailable(string $sql, string $key, $value): array
{
    $name = PDODBController::query($sql);
    if (!$name) return JsonValidate($key . ": " . $value . Message::exists);
    return [];
}
function validateUuid(string $sql): array
{
    return PDODBController::query($sql);
}
