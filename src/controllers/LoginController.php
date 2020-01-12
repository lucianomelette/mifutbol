<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Project;
 
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
		unset($_SESSION['project_session']);
		
		return $response->withJson([
			'status' => 'ok',
		]);
	}
	
	// password recovery
	public function passwordRecovery($request, $response)
	{	
		return $this->container->renderer->render($response, 'login_password_recovery.phtml');
	}
	
	public function sendTemporaryPassword($request, $response, $args)
	{	
		return $response->withJson([
			'status' => 'ok'
		]);
	}
	
	// companies options
	public function companiesSelection($request, $response)
	{	
		unset($_SESSION['company_session']);
		unset($_SESSION['project_session']);
	
		$companies = User::find($_SESSION["user_session"]->id)
					->companies;
	
		$args = [
			"navbar" => [
				"username_session" => $_SESSION["user_session"]->username,
			],
			"companies" => $companies,
		];
		
		return $this->container->renderer->render($response, 'login_companies_selection.phtml', $args);
	}
	
	public function companySelected($request, $response, $args)
	{	
		$_SESSION["company_session"] = Company::find($args["company_id"]);
		
		return $response->withJson([
			'status' => 'ok',
		]);
	}
	
	// projects options
	public function projectsSelection($request, $response)
	{	
		unset($_SESSION['project_session']);
	
		$projects = User::find($_SESSION["user_session"]->id)
					->projects
					->where('company_id', $_SESSION["company_session"]->id);
	
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"company_session" 	=> $_SESSION["company_session"]->business_name,
			],
			"projects" => $projects,
		];
		
		return $this->container->renderer->render($response, 'login_projects_selection.phtml', $args);
	}
	
	public function projectSelected($request, $response, $args)
	{	
		$_SESSION["project_session"] = Project::find($args["project_id"]);
		
		return $response->withJson([
			'status' => 'ok',
		]);
	}
}