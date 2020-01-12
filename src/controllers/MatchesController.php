<?php

namespace App\Controllers;

use App\Models\Match;

class MatchesController extends Controller
{
	public function __invoke($request, $response, $params)
	{	
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
			],
		];
		
		if (isset($params["matchId"]) and $params["matchId"] > 0)
		{
			$args["matchId"] = $params["matchId"];
		}
	
		return $this->container->renderer->render($response, 'matches.phtml', $args);
	}
	
	public function action($request, $response, $args)
	{	
		switch ($args['action'])
		{
			case 'one': return $this->one($request, $response, $args);
			case 'read': return $this->read($request, $response, $args);
			case 'create': return $this->create($request, $response, $args);
			case 'update': return $this->update($request, $response, $args);
			case 'remove': return $this->remove($request, $response, $args);
			case 'options': return $this->options($request, $response, $args);
			default: return $this->error($request, $response, $args);
		}
	}
	
	private function one($request, $response, $args)
	{
		$matchId = $args["matchId"];
		
		$match = Match::find($matchId);
		if ($match != null)
		{
			return $response->withJson([
				"Result" 	=> "OK",
				"Match" 	=> $match,
			]);
		}
		
		return $response->withJson([
			"Result"	=> "ERROR",
			"Message"	=> "No se encuentra el partido.",
		]);
	}
	
	private function read($request, $response, $args)
	{
		$pageSize       = $request->getQueryParam("jtPageSize", $default = null);
		$startIndex     = $request->getQueryParam("jtStartIndex", $default = null);
		
		$records = Match::where('is_canceled', 0)
								->orderBy('dated_at', 'ASC')
								->when($pageSize != null and $startIndex != null, function($query) use ($pageSize, $startIndex) {
									$query->take($pageSize)
										->skip($startIndex);
								})
								->get();
								
		$recordsCount = Match::where('is_canceled', 0)->count();
									
		return $response->withJson([
			"Result" 			=> "OK",
			"Records"			=> $records,
			"TotalRecordCount"	=> $recordsCount,
		]);
	}
	
	private function create($request, $response, $params)
	{
		$body = $request->getParsedBody();
		
		// save match
		$matchId = Match::create($body)->id;

		return $response->withJson([
			'Result'	=> 'OK',
			'Message'	=> 'Partido guardado.',
		]);
	}
	
	private function update($request, $response, $params)
	{
		$body = $request->getParsedBody();
		
		// update match
		$matchId = $body["id"];
		Match::find($matchId)->update($body);
				
		return $response->withJson([
			'Result'	=> 'OK',
			'Message'	=> 'Partido actualizado.',
		]);
	}
	
	private function remove($request, $response, $args)
	{
		$id = $request->getParsedBody()["id"];
		
		Match::find($id)->update([ "is_canceled" => true ]);
		
		return $response->withJson([
			"Result" 	=> "OK",
			"Message"	=> "Partido eliminado."
		]);
	}
	
	public function query($request, $response, $args)
	{
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->display_name,
			],
		];
	
		return $this->container->renderer->render($response, 'matches_query.phtml', $args);
	}
}