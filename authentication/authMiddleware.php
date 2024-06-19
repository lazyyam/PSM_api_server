<?php
use \Firebase\JWT\JWT;

function authenticate($request, $response, $next) {
    $headers = $request->getHeaders();
    $authorization = $headers['HTTP_AUTHORIZATION'][0] ?? null;

    if (!$authorization) {
        return $response->withJson(["error" => "Unauthorized access"], 401);
    }

    list($jwt) = sscanf($authorization, 'Bearer %s');

    if (!$jwt) {
        return $response->withJson(["error" => "Unauthorized access"], 401);
    }

    try {
        $secretKey = 'your_secret_key';
        $decoded = JWT::decode($jwt, $secretKey, ['HS256']);
        $request = $request->withAttribute('user', $decoded);
    } catch (Exception $e) {
        return $response->withJson(["error" => "Unauthorized access"], 401);
    }

    $response = $next($request, $response);
    return $response;
}