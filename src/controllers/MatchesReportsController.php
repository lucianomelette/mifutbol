<?php

namespace App\Controllers;

use App\Models\Match;
 
class MatchesReportsController extends Controller
{
	public function __invoke($request, $response, $params)
	{
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->display_name,
			],
		];		
		
		return $this->container->renderer->render($response, 'matches_pivot.phtml', $args);
	}
	
	public function pivot($request, $response, $args)
	{
		$matches = Match::where('is_canceled', false)->get();
	                                    
		foreach($matches as $match)
		{			
	        array_push($records, (object)[
                "ID"            => $match->id,
                "Fecha"         => $match->dated_at,
                "Blancos"       => $match->white_result,
                "Negros"        => $match->black_result,
                "Comentarios"   => $match->comments,
            ]);
	    }
	    				
		return $response->withJson([
			"Result" 	=> "OK",
			"Records" 	=> $records,
		]);
	}
	
}