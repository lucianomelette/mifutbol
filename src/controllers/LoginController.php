<?php

namespace App\Controllers;

use App\Models\User;
 
class LoginController extends Controller
{
	public function __invoke($request, $response)
	{	
		return $this->container->renderer->render($response, 'login.phtml');
	}
	
	public function login($request, $response, $args)
	{
		$username = $request->getParsedBody()['username'];
		$password = $request->getParsedBody()['password'];
		
		$user = User::where('username', $username)
					->where('password', $password)
					->first();
		
		if ($user != null) {
			$_SESSION['user_session'] = $user;
			
			return $response->withJson([ 'status' => 'ok' ]);
		}
		
		return $response->withJson([ 'status' => 'error' ]);
	}
	
	public function logout($request, $response, $args)
	{
		unset($_SESSION['user_session']);
		
		return $response->withJson([
			'status' => 'ok',
		]);
	}
}