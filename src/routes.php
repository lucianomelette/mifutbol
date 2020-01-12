<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function($request, $response) {
	return $response->withRedirect($this->router->pathFor('matches'));
});

$app->group('/login', function() use ($sessionAuth, $companyAuth) {
	$this->get('', 'LoginController')->setName('login');
	$this->post('', 'LoginController:login');
	$this->post('/logout', 'LoginController:logout');
})->add($hostAuth)->add($sessionAuth);

// matches
$app->group('/matches', function() {
	// pivot
	$this->get('/pivot', 'MatchesReportsController');
	$this->post('/pivot', 'MatchesReportsController:pivot');
	
	// query
	$this->get('/query', 'MatchesController:query');
	
	// general
	$this->get('[/{matchId}]', 'MatchesController')->setName('matches');
	$this->post('/{action}[/{matchId}]', 'MatchesController:action');
})->add($hostAuth)->add($sessionAuth);
