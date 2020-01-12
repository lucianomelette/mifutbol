<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$hostAuth = function ($request, $response, $next) {
    if ($request->getUri()->getBaseUrl() === 'http://yoladrillo.hol.es'
        || $request->getUri()->getBaseUrl() === 'http://yoladrillo.com'
        || $request->getUri()->getBaseUrl() === 'http://yoladrillo.com.ar') {
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

$companyAuth = function ($request, $response, $next) {
	if (isset($_SESSION['company_session'])) {
		return $next($request, $response);
	}
	else {
		return $response->withRedirect($this->router->pathFor('companies_selection'));
	}
};

$appAuth = function ($request, $response, $next) {
	if (isset($_SESSION['project_session'])) {
		return $next($request, $response);
	}
	else {
		return $response->withRedirect($this->router->pathFor('projects_selection'));
	}
};
