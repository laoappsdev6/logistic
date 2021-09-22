<?php
require_once "vendor/firebase/php-jwt/src/BeforeValidException.php";
require_once "vendor/firebase/php-jwt/src/ExpiredException.php";
require_once "vendor/firebase/php-jwt/src/SignatureInvalidException.php";
require_once "vendor/firebase/php-jwt/src/JWT.php";

function registerToken(array $user)
{
    $key = "31ZlkkPKf2kBSARuYmwpfes6FyobGOfF";
    $payload = array(
        "iss" => 'laoapps.com',
        "aud" => "jwt.laoapps.com",
        "iat" => 1356999524,
        "nbf" => 1357000000,
        "data" => $user,
        "updatetime" => tickTime()
    );
    $jwt = JWT::encode($payload, $key);

    return $jwt;
}

function authorizeToken(string $jwt)
{
    try {
        $key = "31ZlkkPKf2kBSARuYmwpfes6FyobGOfF";
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        $decoded_array = (array) $decoded;

        $user = $decoded_array['data'];
        if (isset($user)) {
            return $user->password;
        }
    } catch (Exception $e) {
        return null;
    }

    return null;
}
function checkToken(string $jwt): int
{
    try {
        $key = "31ZlkkPKf2kBSARuYmwpfes6FyobGOfF";
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        $decoded_array = (array) $decoded;

        $user = $decoded_array['data'];
        if (isset($user)) {
            return $user->id;
        }
    } catch (Exception $e) {
        return 0;
    }
    return 0;
}
function allDetailsToken(string $jwt)
{
    $key = "31ZlkkPKf2kBSARuYmwpfes6FyobGOfF";
    $decoded = JWT::decode($jwt, $key, array('HS256'));
    $decoded_array = (array) $decoded;

    $user = $decoded_array['data'];
    if (isset($user)) {
        return $user;
    }
    return null;
}
function unitSpeedToken(string $jwt)
{
    $key = "31ZlkkPKf2kBSARuYmwpfes6FyobGOfF";
    $decoded = JWT::decode($jwt, $key, array('HS256'));
    $decoded_array = (array) $decoded;

    $user = $decoded_array['data'];
    if (isset($user)) {
        return $user->us;
    }
    return 0;
}
function timeZoneToken(string $jwt)
{
    $key = "31ZlkkPKf2kBSARuYmwpfes6FyobGOfF";
    $decoded = JWT::decode($jwt, $key, array('HS256'));
    $decoded_array = (array) $decoded;

    $user = $decoded_array['data'];
    if (isset($user)) {
        return $user->client_time_zone;
    }
    return 0;
}
function refreshToken(string $jwt)
{
    $key = "31ZlkkPKf2kBSARuYmwpfes6FyobGOfF";
    $decoded = JWT::decode($jwt, $key, array('HS256'));
    $decoded_array = (array) $decoded;
    return registerToken($decoded_array['data']);
}
function tickTime()
{
    $mt = microtime(true);
    $mt =  $mt * 1000; //microsecs
    return (string)$mt * 10; //100 Nanosecs
}
