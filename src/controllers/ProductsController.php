<?php

namespace App\Controllers;

use App\Models\Product as Model;
 
class ProductsController extends Controller
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
	
		return $this->container->renderer->render($response, 'products.phtml', $args);
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
		$pageSize       = $request->getQueryParam("jtPageSize", $default = null);
		$startIndex     = $request->getQueryParam("jtStartIndex", $default = null);
		
		$records = Model::where('company_id', $_SESSION["company_session"]->id)
						->when($pageSize != null, function($q) use ($pageSize, $startIndex) {
							$q->take($pageSize)
								->skip($startIndex);
						})
						->orderBy('description')
						->get();
						
		$recordsCount = Model::where('company_id', $_SESSION["company_session"]->id)
							->count();
		
		return $response->withJson([
			"Result" 			=> "OK",
			"Records"			=> $records,
			"TotalRecordCount"	=> $recordsCount,
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
		$options = Model::where('company_id', $_SESSION["company_session"]->id)
							->selectRaw("id as Value, CONCAT(unique_code, ' | ', description) as DisplayText")
							->orderBy('description', 'asc')
							->get();
		
		return $response->withJson([
			"Result" 	=> "OK",
			"Options"	=> $options,
		]);
	}
}