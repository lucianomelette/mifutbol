<?php

namespace App\Controllers;

use App\Models\Club as Club;
 
class ApiController extends Controller
{
	public function __invoke($request, $response, $args)
	{	
		$club = Club::where('api_key', $args['api_key'])->first();
	
		if ($club != null)
		{
			switch ($args['action'])
			{
				case 'tournaments': return $this->tournaments($request, $response, $club);
				case 'categories': return $this->categories($request, $response, $club);
				case 'draw': return $this->draw($request, $response, $club);
				case 'ranking': return $this->ranking($request, $response, $club);
				default: return $this->error($request, $response, $club);
			}
		}
		else
		{
			return $response->withJson([
				"Result" 	=> "ERROR",
				"Message"	=> "Non authorized API Key",
			]);
		}
	}
	
	private function tournaments($request, $response, $club)
	{
		$records = $club->tournaments()
						->where('is_active', 1)
						->get();
		
		return $response->withJson([
			"Result" 	=> "OK",
			"Records"	=> $records,
		]);
	}
	
	private function categories($request, $response, $club)
	{
		if (isset($request->getParsedBody()['tournament_id']))
		{
			$tournament_id = $request->getParsedBody()['tournament_id'];
			$records = $club->tournaments()
							->find($tournament_id)
							->categories;
		}
		else
		{
			$records = $club->categories;
		}

		return $response->withJson([
			"Result" 	=> "OK",
			"Records"	=> $records,
		]);
	}
	
	private function draw($request, $response, $club)
	{
		$tournament_id 	= $request->getParsedBody()['tournament_id'];
		$category_id 	= $request->getParsedBody()['category_id'];
		
		return $response->withJson([
			"Result" 	=> "OK",
			"Draw"		=> (new \App\Controllers\MatchesController())->getDraw($tournament_id, $category_id),
		]);
	}
	
	private function ranking($request, $response, $club)
	{
		$category_id = $request->getParsedBody()['category_id'];
		
		return $response->withJson([
			"Result" 	=> "OK",
			"Ranking"	=> (new \App\Controllers\RankingsController())->getRanking($club, $category_id),
		]);
	}
}