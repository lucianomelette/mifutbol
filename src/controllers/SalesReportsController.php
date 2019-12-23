<?php

namespace App\Controllers;

use App\Models\SaleHeader;
use App\Models\CollectionHeader;
use App\Models\Exchange;

use Carbon\Carbon;
 
class SalesReportsController extends Controller
{
	public function __invoke($request, $response, $params)
	{
		$company = $_SESSION["company_session"];
		$company->load('customers');
		
		$project = $_SESSION["project_session"];
		$project->load('salesDocumentsTypes');
		$project->load('collectionsDocumentsTypes');
		
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"customers" 			=> $company->customers->sortBy("business_name"),
			"salesDocsTypes" 		=> $project->salesDocumentsTypes->sortBy("description"),
			"collectionsDocsTypes" 	=> $project->collectionsDocumentsTypes->sortBy("description"),
		];		
		
		return $this->container->renderer->render($response, 'sales_report.phtml', $args);
	}
	
	public function report($request, $response, $args)
	{
		$customers_ids 			= (isset($request->getParsedBody()["customers_ids"]) ? $request->getParsedBody()["customers_ids"] : null);
		$sales_docs_codes		= (isset($request->getParsedBody()["sales_docs_codes"]) ? $request->getParsedBody()["sales_docs_codes"] : null);
		$collections_docs_codes	= (isset($request->getParsedBody()["collections_docs_codes"]) ? $request->getParsedBody()["collections_docs_codes"] : null);
		
		$sales = SaleHeader::where('project_id', $_SESSION['project_session']->id)
										->where('is_canceled', false)
										->when($customers_ids != null, function($query) use ($customers_ids) {
											$query->whereIn('customer_id', $customers_ids);
										})
										->when($sales_docs_codes != null, function($query) use ($sales_docs_codes) {
											$query->whereIn('document_type_code', $sales_docs_codes);
										})
	                                    ->orderBy('dated_at', 'ASC')
	                                    ->get();
	                                    
	    $collections = CollectionHeader::where('project_id', $_SESSION['project_session']->id)
	                                    ->where('is_canceled', false)
										->when($customers_ids != null, function($query) use ($customers_ids) {
											$query->whereIn('customer_id', $customers_ids);
										})
										->when($collections_docs_codes != null, function($query) use ($collections_docs_codes) {
											$query->whereIn('document_type_code', $collections_docs_codes);
										})
	                                    ->orderBy('dated_at', 'ASC')
	                                    ->get();
	    $records = Array();
	    
	    $sal = 0;
	    $col = 0;
		
		$balanceARS = 0;
		$balanceUSD = 0;
		
		$find 		= ['Ñ', 'ñ', 'á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'];
		$replace 	= ['N', 'n', 'a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'];
		
	    while (count($sales) > $sal or count($collections) > $col)
	    {
			$description 	= "";
			$exchangePrice 	= 1;
			
			// take older first...
			if ( !isset($collections[$col]) or (isset($sales[$sal]) and $sales[$sal]->dated_at <= $collections[$col]->dated_at) )
			{
	            $document = $sales[$sal];
	            $sal++;
				
				// description
				if ( count($document->details) > 0 ) {
					$item = $document->details->first();
					if ($item != null) {
						$description = strtoupper(str_replace($find, $replace, $item->product_description));
					}
				}
				
				// daily exchange
				$exchange = Exchange::where('dated_at', $document->dated_at)
									->where('currency_code', 'USD')->first();
				
				if ($exchange != null and $exchange->price != 0) {
					$exchangePrice = $exchange->price;
				}
	        }
	        else {
	            $document = $collections[$col];
	            $col++;
				
				// description
				$description = strtoupper(str_replace($find, $replace, $document->comments));
				
				// document exchange
				if ($document->exchange != 0)
					$exchangePrice = $document->exchange;
				else
				{
					// daily exchange
					$exchange = Exchange::where('dated_at', $document->dated_at)
									->where('currency_code', 'USD')->first();
				
					if ($exchange != null and $exchange->price != 0) {
						$exchangePrice = $exchange->price;
					}
				}
	        }
	        
			// subtotals
			$document->total *= $document->documentType->balance_multiplier;
			
			if ($document->documentType->currency_code == 'ARS') {
				$subtotalARS = $document->total;
				$subtotalUSD = $document->total / $exchangePrice;
			}
			elseif ($document->documentType->currency_code == 'USD') {
				$subtotalARS = $document->total * $exchangePrice;
				$subtotalUSD = $document->total;
			}
			else {
				return $response->write('Error de seteo de moneda');
			}
			
			// balance
			$balanceARS	+= $subtotalARS;
			$balanceUSD += $subtotalUSD;
			
	        array_push($records, (object)[
                "id"            => $document->id,
                "dated_at"      => $document->dated_at,
                "number"        => $document->number,
                "unique_code"   => $document->document_type_code,
                "business_name" => $document->customer->business_name,
				"description"	=> $description,
                "exchange"		=> $this->parsedFloat($exchangePrice, 2),
				"totalARS"		=> $this->parsedFloat($subtotalARS, 2),
				"totalUSD"		=> $this->parsedFloat($subtotalUSD, 2),
				"balanceARS"	=> $this->parsedFloat($balanceARS, 2),
				"balanceUSD"	=> $this->parsedFloat($balanceUSD, 2),
            ]);
	    }
	    				
		$responseHTML =	$this->padr("ID", 6, " ") .
						$this->padr("FECHA", 12, " ") .
						$this->padr("TIPO", 6, " ") .
						$this->padr("NUMERO", 15, " ") .
						$this->padr("PROVEEDOR", 20, " ") .
						$this->padr("DESCRIPCION", 15, " ") .
						$this->padl("CAMBIO", 7, " ") .
						$this->padl("TOTAL AR", 14, " ") .
						$this->padl("TOTAL USD", 14, " ") .
						$this->padl("SALDO AR", 14, " ") .
						$this->padl("SALDO USD", 14, " ") . "\n" .
						str_repeat("-", 137) . "\n";
		
		foreach($records as $record)
	    {
	        $responseHTML .=	$this->padr($record->id, 6, " ") .
	                            $this->padr($record->dated_at, 12, " ") .
	                            $this->padr($record->unique_code, 6, " ") .
	                            $this->padr($record->number, 15, " ") .
	                            $this->padr($record->business_name, 20, " ", " ") .
	                            $this->padr($record->description, 15, " ", " ") .
								$this->padl($record->exchange, 7, " ") .
	                            $this->padl($record->totalARS, 14, " ") .
								$this->padl($record->totalUSD, 14, " ") .
	                            $this->padl($record->balanceARS, 14, " ") .
	                            $this->padl($record->balanceUSD, 14, " ") . "\n";
	                            
	    }
		
		$responseHTML .=	str_repeat("=", 137) . "\n" .
							$this->padr("", 6, " ") .
							$this->padr("", 12, " ") .
							$this->padr("", 6, " ") .
							$this->padr("", 15, " ") .
							$this->padr("", 20, " ") .
							$this->padr("", 15, " ") .
							$this->padl("", 7, " ") .
							$this->padl($this->parsedFloat($balanceARS, 2), 14, " ") .
							$this->padl($this->parsedFloat($balanceUSD, 2), 14, " ") .
							$this->padl("", 14, " ") .
							$this->padl("", 14, " ");
		
		//return $response->write($responseHTML);
		
		return $response->withJson([
			"Result" 	=> "OK",
			"Records" 	=> html_entity_decode($responseHTML, ENT_QUOTES, "UTF-8"),
		]);
	}
	
	private function padr($str, $len, $char, $lastChar = "")
	{
	    $str = trim($str);
	    if (strlen($str) > $len)
	        $result = substr($str, 0, $len);
		else
			$result = str_pad($str, $len, $char, STR_PAD_RIGHT);
		
		// last char
		if ($lastChar != "")
			$result[strlen($result)-1] = $lastChar;
		
		return $result;
	}
	
	private function padl($str, $len, $char)
	{
	    $str = trim($str);
	    if (strlen($str) > $len)
	        return substr($str, 0, $len);
	    return str_pad($str, $len, $char, STR_PAD_LEFT);
	}
	
	private function parsedFloat($num, $dec = 2)
	{
		if ($num != 0) {
			$rounded	= round($num * pow(10, $dec), 0);
			$strnum   	= strval($rounded);
			$intval     = substr($strnum, 0, strlen($strnum) - $dec);
			$decval     = substr($strnum, strlen($strnum) - $dec);
			
			return $intval . "." . $decval;
		}
		
		return "0." . str_repeat("0", $dec);
	}
	
	public function pivot($request, $response, $args)
	{
	    $company = $_SESSION["company_session"];
		$company->load('customers');
		
		$project = $_SESSION["project_session"];
		$project->load('salesDocumentsTypes');
		$project->load('collectionsDocumentsTypes');
	    
	    $args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"customers" 			=> $company->customers->sortBy("business_name"),
			"salesDocsTypes" 		=> $project->salesDocumentsTypes->sortBy("description"),
			"collectionsDocsTypes" 	=> $project->collectionsDocumentsTypes->sortBy("description"),
		];		
	    
	    return $this->container->renderer->render($response, 'sales_pivot.phtml', $args);
	}
	
	public function pivotData($request, $response, $args)
	{
		$customers_ids 			= (isset($request->getParsedBody()["customers_ids"]) ? $request->getParsedBody()["customers_ids"] : null);
		$sales_docs_codes		= (isset($request->getParsedBody()["sales_docs_codes"]) ? $request->getParsedBody()["sales_docs_codes"] : null);
		$collections_docs_codes	= (isset($request->getParsedBody()["collections_docs_codes"]) ? $request->getParsedBody()["collections_docs_codes"] : null);
		
		$sales = SaleHeader::where('project_id', $_SESSION['project_session']->id)
										->where('is_canceled', false)
										->when($customers_ids != null, function($query) use ($customers_ids) {
											$query->whereIn('customer_id', $customers_ids);
										})
										->when($sales_docs_codes != null, function($query) use ($sales_docs_codes) {
											$query->whereIn('document_type_code', $sales_docs_codes);
										})
	                                    ->orderBy('dated_at', 'ASC')
	                                    ->get();
	                                    
	    $collections = CollectionHeader::where('project_id', $_SESSION['project_session']->id)
	                                    ->where('is_canceled', false)
										->when($customers_ids != null, function($query) use ($customers_ids) {
											$query->whereIn('customer_id', $customers_ids);
										})
										->when($collections_docs_codes != null, function($query) use ($collections_docs_codes) {
											$query->whereIn('document_type_code', $collections_docs_codes);
										})
	                                    ->orderBy('dated_at', 'ASC')
	                                    ->get();
	    $records = Array();
	    
	    $sal = 0;
	    $col = 0;
		
	    while (count($sales) > $sal or count($collections) > $col)
	    {
			$description 	= "";
			$exchangePrice 	= 1;
			
			// take older first...
			if ( !isset($collections[$col]) or (isset($sales[$sal]) and $sales[$sal]->dated_at <= $collections[$col]->dated_at) )
			{
	            $document = $sales[$sal];
	            $sal++;
				
				// description
				if ( count($document->details) > 0 ) {
					$item = $document->details->first();
					if ($item != null) {
						$description = strtoupper(str_replace($find, $replace, $item->product_description));
					}
				}
				
				// daily exchange
				$exchange = Exchange::where('dated_at', $document->dated_at)
									->where('currency_code', 'USD')->first();
				
				if ($exchange != null and $exchange->price != 0) {
					$exchangePrice = $exchange->price;
				}
	        }
	        else {
	            $document = $collections[$col];
	            $col++;
				
				// description
				$description = strtoupper(str_replace($find, $replace, $document->comments));
				
				// document exchange
				if ($document->exchange != 0)
					$exchangePrice = $document->exchange;
				else
				{
					// daily exchange
					$exchange = Exchange::where('dated_at', $document->dated_at)
									->where('currency_code', 'USD')->first();
				
					if ($exchange != null and $exchange->price != 0) {
						$exchangePrice = $exchange->price;
					}
				}
	        }
	        
			// subtotals
			$document->total *= $document->documentType->balance_multiplier;
			
			if ($document->documentType->currency_code == 'ARS') {
				$subtotalARS = $document->total;
				$subtotalUSD = $document->total / $exchangePrice;
			}
			elseif ($document->documentType->currency_code == 'USD') {
				$subtotalARS = $document->total * $exchangePrice;
				$subtotalUSD = $document->total;
			}
			else {
				return $response->write('Error de seteo de moneda');
			}
			
	        array_push($records, (object)[
                "ID"            => $document->id,
                "Fecha"         => $document->dated_at,
                "Número"        => $document->number,
                "Código"        => $document->document_type_code,
                "Comprobante"   => $document->documentType->description,
                "Cliente"       => $document->customer->business_name,
				"Descripción"	=> $description,
                "Cambio"		=> $this->parsedFloat($exchangePrice, 2),
				"Total AR"		=> $this->parsedFloat($subtotalARS, 2),
				"Total US"		=> $this->parsedFloat($subtotalUSD, 2),
            ]);
	    }
	    				
		return $response->withJson([
			"Result" 	=> "OK",
			"Records" 	=> $records,
		]);
	}
	
}