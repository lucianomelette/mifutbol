<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$hostAuth = function ($request, $response, $next) {
    if ($request->getUri()->getBaseUrl() === 'http://mifutbol.pe.hu') {
        return $next($request, $response);
    }
    else {
        return $response->withJson(["error_message" => "unauthorized host"]);
    }
};

$sessionAuth = function ($request, $response, $next) {
    if (isset($_SESSION['user_session'])) {
        return $next($request, $response);
    }
    else {
        return $response->withRedirect($this->router->pathFor('login'));
    }
};
