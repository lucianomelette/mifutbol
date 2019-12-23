<?php

namespace App\Controllers;

use App\Models\PurchaseHeader;
use App\Models\PaymentHeader;
use App\Models\Exchange;

use Carbon\Carbon;
 
class PurchasesReportsController extends Controller
{
	public function __invoke($request, $response, $params)
	{
		$company = $_SESSION["company_session"];
		$company->load('suppliers');
		
		$project = $_SESSION["project_session"];
		$project->load('purchasesDocumentsTypes');
		$project->load('paymentsDocumentsTypes');
		
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"suppliers" 			=> $company->suppliers->sortBy("business_name"),
			"purchasesDocsTypes" 	=> $project->purchasesDocumentsTypes->sortBy("description"),
			"paymentsDocsTypes" 	=> $project->paymentsDocumentsTypes->sortBy("description"),
		];		
		
		return $this->container->renderer->render($response, 'purchases_report.phtml', $args);
	}
	
	public function report($request, $response, $args)
	{
		$body = $request->getParsedBody();
		
		$suppliers_ids 			= (isset($body["suppliers_ids"]) ? $body["suppliers_ids"] : null);
		$purchases_docs_codes	= (isset($body["purchases_docs_codes"]) ? $body["purchases_docs_codes"] : null);
		$payments_docs_codes	= (isset($body["payments_docs_codes"]) ? $body["payments_docs_codes"] : null);
		
		$purchases = PurchaseHeader::where('project_id', $_SESSION['project_session']->id)
										->where('is_canceled', false)
										->when($suppliers_ids != null, function($query) use ($suppliers_ids) {
											$query->whereIn('supplier_id', $suppliers_ids);
										})
										->when($purchases_docs_codes != null, function($query) use ($purchases_docs_codes) {
											$query->whereIn('document_type_code', $purchases_docs_codes);
										})
	                                    ->orderBy('dated_at', 'ASC')
	                                    ->get();
	                                    
	    $payments = PaymentHeader::where('project_id', $_SESSION['project_session']->id)
	                                    ->where('is_canceled', false)
										->when($suppliers_ids != null, function($query) use ($suppliers_ids) {
											$query->whereIn('supplier_id', $suppliers_ids);
										})
										->when($payments_docs_codes != null, function($query) use ($payments_docs_codes) {
											$query->whereIn('document_type_code', $payments_docs_codes);
										})
	                                    ->orderBy('dated_at', 'ASC')
	                                    ->get();
	    $records = Array();
	    
	    $pur = 0;
	    $pay = 0;
		
		$balanceARS = 0;
		$balanceUSD = 0;
		
		$find 		= ['Ñ', 'ñ', 'á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'];
		$replace 	= ['N', 'n', 'a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'];
		
	    while (count($purchases) > $pur or count($payments) > $pay)
	    {
			$description 	= "";
			$exchangePrice 	= 1;
			
			// take older first...
			if ( !isset($payments[$pay]) or (isset($purchases[$pur]) and $purchases[$pur]->dated_at <= $payments[$pay]->dated_at) ) {
	            $document = $purchases[$pur];
	            $pur++;
				
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
	            $document = $payments[$pay];
	            $pay++;
				
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
                "business_name" => $document->supplier->business_name,
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
}