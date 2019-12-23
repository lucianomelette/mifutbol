<?php

namespace App\Controllers;

use App\Models\CollectionHeader;
use App\Models\PaymentHeader;
use App\Models\Exchange;

use Carbon\Carbon;
 
class CashReportsController extends Controller
{
	public function __invoke($request, $response, $params)
	{
		$company = $_SESSION["company_session"];
		$project = $_SESSION["project_session"];
		$project->load('collectionsDocumentsTypes');
		$project->load('paymentsDocumentsTypes');
		
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"collectionsDocsTypes" 	=> $project->collectionsDocumentsTypes->sortBy("description"),
			"paymentsDocsTypes" 	=> $project->paymentsDocumentsTypes->sortBy("description"),
		];		
		
		return $this->container->renderer->render($response, 'cash_report.phtml', $args);
	}
	
	public function report($request, $response, $args)
	{
		$body = $request->getParsedBody();
		
		$payment_method_code 	= (isset($body["payment_method_code"]) ? $body["payment_method_code"] : null);
		$collections_docs_codes	= (isset($body["collections_docs_codes"]) ? $body["collections_docs_codes"] : null);
		$payments_docs_codes	= (isset($body["payments_docs_codes"]) ? $body["payments_docs_codes"] : null);
		
		$split = explode("-", $payment_method_code);
		
		$type = $split[0];
		
		$collections = CollectionHeader::where('project_id', $_SESSION['project_session']->id)
										->where('is_canceled', false)
										->when($collections_docs_codes != null, function($query) use ($collections_docs_codes) {
											$query->whereIn('document_type_code', $collections_docs_codes);
										})
										->when($type == 'cash', function($q1) use ($split) {
											$q1->whereHas('detailsCash', function($q2) use ($split) {
												$q2->where('currency_code', strtoupper($split[1]));
											});
										})
										->when($type == 'third.party.check', function($q1) {
											$q1->whereHas('detailsThirdPartyChecks');
										})
										->orderBy('dated_at', 'ASC')
										->get();
				
		$payments = PaymentHeader::where('project_id', $_SESSION['project_session']->id)
									->where('is_canceled', false)
									->when($payments_docs_codes != null, function($query) use ($payments_docs_codes) {
										$query->whereIn('document_type_code', $payments_docs_codes);
									})
									->when($type == 'cash', function($q1) use ($split) {
										$q1->whereHas('detailsCash', function($q2) use ($split) {
											$q2->where('currency_code', strtoupper($split[1]));
										});
									})
									->when($type == 'third.party.check', function($q1) {
										$q1->whereHas('detailsThirdPartyChecks');
									})
									->orderBy('dated_at', 'ASC')
									->get();
	    
	    $records = Array();
	    
	    $col = 0;
	    $pay = 0;
		
		$balanceARS = 0;
		$balanceUSD = 0;
		
		$find 		= ['Ñ', 'ñ', 'á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'];
		$replace 	= ['N', 'n', 'a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'];
		
	    while (count($collections) > $col or count($payments) > $pay)
	    {
			$module			= "";
			$description 	= "";
			$businessName 	= "";
			$exchangePrice 	= 1;
			
			// take older first...
			if ( !isset($payments[$pay]) or (isset($collections[$col]) and $collections[$col]->dated_at <= $payments[$pay]->dated_at) ) {
	            $document = $collections[$col];
	            $col++;
				
				// module
				$module = "V";
				
				// description
				$description = strtoupper(str_replace($find, $replace, $document->comments));
				
				// business name
				$businessName = $document->customer->business_name;
				
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
	        else {
	            $document = $payments[$pay];
	            $pay++;
				
				// module
				$module = "C";
				
				// description
				$description = strtoupper(str_replace($find, $replace, $document->comments));
				
				// business name
				$businessName = $document->supplier->business_name;
				
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
			/*$document->total *= $document->documentType->balance_multiplier;
			
			if ($document->documentType->currency_code == 'ARS') {
				$subtotalARS = $document->detailsCash->;
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
			$balanceUSD += $subtotalUSD;*/
			
			$balanceMultiplier = $document->documentType->balance_multiplier;
			
			switch($type)
			{
				case "cash":
					$details = $document->detailsCash; break;
					
				case "third.party.check":
					$details = $document->detailsThirdPartyChecks; break;
			}
			
			foreach($details as $detail)
			{
				array_push($records, (object)[
					"id"            => $document->id,
					"module"        => $module,
					"dated_at"      => $document->dated_at,
					"number"        => $document->number,
					"unique_code"   => $document->document_type_code,
					"business_name" => $businessName,
					"description"	=> $description,
					"method"		=> $payment_method_code,
					"amount"		=> $this->parsedFloat($detail->amount * $balanceMultiplier, 2),
				]);
			}
	    }
	    				
		$responseHTML =	$this->padr("ID", 6, " ") .
						$this->padr("#", 3, " ") .
						$this->padr("FECHA", 12, " ") .
						$this->padr("TIPO", 6, " ") .
						$this->padr("NUMERO", 15, " ") .
						$this->padr("RAZON SOCIAL", 20, " ") .
						$this->padr("DESCRIPCION", 15, " ") .
						$this->padr("METODO", 15, " ") .
						$this->padl("MONTO", 14, " ") . "\n" .
						str_repeat("-", 137) . "\n";
		
		foreach($records as $record)
	    {
	        $responseHTML .=	$this->padr($record->id, 6, " ") .
	                            $this->padr($record->module, 3, " ") .
	                            $this->padr($record->dated_at, 12, " ") .
	                            $this->padr($record->unique_code, 6, " ") .
	                            $this->padr($record->number, 15, " ") .
	                            $this->padr($record->business_name, 20, " ", " ") .
	                            $this->padr($record->description, 15, " ", " ") .
								$this->padr($record->method, 15, " ") .
	                            $this->padl($record->amount, 14, " ") . "\n";
	                            
	    }
		
		$responseHTML .=	str_repeat("=", 137) . "\n" .
							$this->padr("", 6, " ") .
							$this->padr("", 3, " ") .
							$this->padr("", 12, " ") .
							$this->padr("", 6, " ") .
							$this->padr("", 15, " ") .
							$this->padr("", 20, " ") .
							$this->padr("", 15, " ") .
							$this->padr("", 15, " ") .
							$this->padl(0, 14, " ");
		
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
	
	public function pivot($request, $response, $params)
	{
		$company = $_SESSION["company_session"];
		$project = $_SESSION["project_session"];
		$project->load('collectionsDocumentsTypes');
		$project->load('paymentsDocumentsTypes');
		
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"collectionsDocsTypes" 	=> $project->collectionsDocumentsTypes->sortBy("description"),
			"paymentsDocsTypes" 	=> $project->paymentsDocumentsTypes->sortBy("description"),
		];		
		
		return $this->container->renderer->render($response, 'cash_pivot.phtml', $args);
	}
	
	public function pivotData($request, $response, $args)
	{
		$body = $request->getParsedBody();
		
		$payment_method_code 	= (isset($body["payment_method_code"]) ? $body["payment_method_code"] : null);
		$collections_docs_codes	= (isset($body["collections_docs_codes"]) ? $body["collections_docs_codes"] : null);
		$payments_docs_codes	= (isset($body["payments_docs_codes"]) ? $body["payments_docs_codes"] : null);
		
		$split = explode("-", $payment_method_code);
		
		$type           = $split[0];
		$currencyCode   = strtoupper($split[1]);

		$companyId = $_SESSION['company_session']->id;
		
		$collections = CollectionHeader::whereHas('project', function($q) use ($companyId) {
											$q->whereHas('company', function($q1) use ($companyId) {
												$q1->where('id', $companyId);
											});
										})
										->where('is_canceled', false)
										->when($collections_docs_codes != null, function($query) use ($collections_docs_codes) {
											$query->whereIn('document_type_code', $collections_docs_codes);
										})
										->when($type == 'cash', function($q1) use ($currencyCode) {
											$q1->whereHas('detailsCash', function($q2) use ($currencyCode) {
												$q2->where('currency_code', $currencyCode);
											});
										})
										->when($type == 'third.party.check', function($q1) {
											$q1->whereHas('detailsThirdPartyChecks');
										})
										->orderBy('dated_at', 'ASC')
										->get();
				
		$payments = PaymentHeader::whereHas('project', function($q) use ($companyId) {
										$q->whereHas('company', function($q1) use ($companyId) {
											$q1->where('id', $companyId);
										});
									})
									->where('is_canceled', false)
									->when($payments_docs_codes != null, function($query) use ($payments_docs_codes) {
										$query->whereIn('document_type_code', $payments_docs_codes);
									})
									->when($type == 'cash', function($q1) use ($currencyCode) {
										$q1->whereHas('detailsCash', function($q2) use ($currencyCode) {
											$q2->where('currency_code', $currencyCode);
										});
									})
									->when($type == 'third.party.check', function($q1) {
										$q1->whereHas('detailsThirdPartyChecks');
									})
									->orderBy('dated_at', 'ASC')
									->get();
	    
	    $records = Array();
	    
	    $col = 0;
	    $pay = 0;
		
	    while (count($collections) > $col or count($payments) > $pay)
	    {
			$module			= "";
			$description 	= "";
			$businessName 	= "";
			$exchangePrice 	= 1;
			
			// take older first...
			if ( !isset($payments[$pay]) or (isset($collections[$col]) and $collections[$col]->dated_at <= $payments[$pay]->dated_at) ) {
	            $document = $collections[$col];
	            $col++;
				
				// module
				$module = "Ventas";
				
				// description
				$description = $document->comments;
				
				// business name
				$businessName = $document->customer->business_name;
				
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
	        else {
	            $document = $payments[$pay];
	            $pay++;
				
				// module
				$module = "Compras";
				
				// description
				$description = $document->comments;
				
				// business name
				$businessName = $document->supplier->business_name;
				
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
			
			$balanceMultiplier = $document->documentType->balance_multiplier;
			
			switch($type)
			{
				case "cash":
				    $tipo = "Efectivo " . $currencyCode;
					$details = $document->detailsCash;
					break;
					
				case "third.party.check":
				    $tipo = "Cheques de Terceros";
					$details = $document->detailsThirdPartyChecks;
					break;
			}
			
			foreach($details as $detail)
			{
			    $value = (object)[
					"ID"          	=> $document->id,
					"Módulo"      	=> $module,
					"Fecha"       	=> $document->dated_at,
					"No. de Comp."  => $document->number,
					"Código"        => $document->document_type_code,
					"Comprobante"   => $document->documentType->description,
					"Razón Social"  => $businessName,
					"Descripción"	=> $description,
					"Tipo"		    => $tipo,
					"Monto"		    => $this->parsedFloat($detail->amount * $balanceMultiplier, 2),
					"Proyecto"		=> $document->project->full_name,
				];
				
				switch($type)
    			{
    				case "third.party.check":
    				    
    				    $key = $detail->bank_id . '_' . $detail->number . '_' . $detail->expiration_at . '_' . $detail->amount;
    				    
    				    $value->{"No. de Cheque"}	= $detail->number;
    				    $value->{"Banco"}      		= $detail->bank->description;
    				    $value->{"Vencimiento"}		= $detail->expiration_at;
    				    $value->{"Clave"}      		= $key;
    				    $value->{"Acumulado"}  		= $value->Monto;
    				    $value->{"En Cartera"} 		= "Sí";
    				    
    				    foreach($records as &$elem)
    				    {
    				        if ($elem->Clave == $key)
    				        {
    				            $elem->Acumulado    += $value->Monto;
    				            $value->Acumulado   = $elem->Acumulado;
    				            
				                $elem->{"En Cartera"}   = ($elem->Acumulado == 0) ? "No" : "Sí";
				                $value->{"En Cartera"}  = ($elem->Acumulado == 0) ? "No" : "Sí";
    				        }
    				    }
    				    
    					break;
    			}
			    
				array_push($records, $value);
			}
	    }
	    				
		return $response->withJson([
			"Result" 	=> "OK",
			"Records" 	=> $records,
		]);
	}
}