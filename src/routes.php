<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function($request, $response) {
	return $response->withRedirect($this->router->pathFor('suppliers'));
});

$app->group('/login', function() use ($sessionAuth, $companyAuth) {
	$this->get('', 'LoginController')->setName('login');
	$this->post('', 'LoginController:login');
	$this->post('/logout', 'LoginController:logout');
})->add($hostAuth);

// matches
$app->group('/matches', function() {
	// pivot
	$this->get('/pivot', 'MatchesReportsController:pivot');
	$this->post('/pivot', 'MatchesReportsController:pivotData');
	
	// query
	$this->get('/query', 'MatchesController:query');
	
	// general
	$this->get('[/{matchId}]', 'MatchesController');
	$this->post('/{action}[/{matchId}]', 'MatchesController:action');
})->add($appAuth)->add($sessionAuth)->add($hostAuth);
