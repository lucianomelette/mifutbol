<?php

namespace App\Controllers;

use App\Models\BankAccount as Model;
 
class BanksAccountsController extends Controller
{
	public function __invoke($request, $response)
	{	
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $_SESSION["project_session"]->full_name,
				"company_session" 	=> $_SESSION["company_session"]->business_name,
			],
		];
	
		return $this->container->renderer->render($response, 'banks_accounts.phtml', $args);
	}

	public function action($request, $response, $args)
	{	
		switch ($args['action'])
		{
			case 'read': return $this->read($request, $response, $args);
			case 'create': return $this->create($request, $response, $args);
			case 'update': return $this->update($request, $response, $args);
			case 'remove': return $this->remove($request, $response, $args);
			case 'options': return $this->options($request, $response, $args);
			default: return $this->error($request, $response, $args);
		}
	}
	
	private function read($request, $response, $args)
	{
		$records = Model::where('company_id', $_SESSION["company_session"]->id)
							->orderBy('alias')
							->get();
		
		return $response->withJson([
			"Result" 	=> "OK",
			"Records"	=> $records,
		]);
	}
	
	private function create($request, $response, $args)
	{
		$newRecord 					= $request->getParsedBody();
		$newRecord['company_id'] 	= $_SESSION["company_session"]->id;
		
		$id = Model::create($newRecord)->id;
		$newRecord['id'] = $id;
		
		return $response->withJson([
			"Result" 	=> "OK",
			"Record"	=> $newRecord,
		]);
	}
	
	private function update($request, $response, $args)
	{
		$updatedRecord = $request->getParsedBody();
		
		Model::find($updatedRecord["id"])
				->update($updatedRecord);
		
		return $response->withJson([
			"Result" 	=> "OK",
			"Record"	=> $updatedRecord,
		]);
	}
	
	private function remove($request, $response, $args)
	{
		$id = $request->getParsedBody()["id"];
		
		Model::find($id)->delete();
		
		return $response->withJson([
			"Result" 	=> "OK",
		]);
	}
	
	private function options($request, $response, $args)
	{
		$records = Model::where('company_id', $_SESSION["company_session"]->id)
							->get();
		
		$options = Array();
		foreach($records as $record) {
		    array_push($options, [
		        'Value'         => $record->id,
		        'DisplayText'   => $record->bank->unique_code . ' | ' . $record->alias . ' | ' . $record->number,
		    ]);
		}
		
		return $response->withJson([
			"Result" 	=> "OK",
			"Options"	=> $options,
		]);
	}
}