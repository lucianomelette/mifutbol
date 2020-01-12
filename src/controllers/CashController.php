<?php

namespace App\Controllers;

use App\Models\CollectionHeader;
use App\Models\CollectionDetailCash;
use App\Models\CollectionDetailMaterial;
use App\Models\CollectionDetailThirdPartyCheck;
use App\Models\CollectionDetailTransfer;
use App\Models\CollectionDocumentType;
 
class CashController extends Controller
{
	public function __invoke($request, $response, $params)
	{	
		$company = $_SESSION["company_session"];
		$company->load('customers');
		$company->load('banks');
		$company->load('banksAccounts');
		$company->load('currencies');
		
		$project = $_SESSION["project_session"];
		$project->load([
			'collectionsDocumentsTypes' => function($q) {
				$q->orderBy("description");
			}
		]);
	
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $_SESSION["project_session"]->full_name,
				"company_session" 	=> $company->business_name,
			],
			"customers" 		=> $company->customers->sortBy("business_name"),
			"documentsTypes" 	=> $project->collectionsDocumentsTypes,
			"banks" 			=> $company->banks->sortBy("description"),
			"banksAccounts" 	=> $company->banksAccounts,
			"currencies" 		=> $company->currencies,
		];
		
		if (isset($params["headerId"]) and $params["headerId"] > 0)
		{
			$args["headerId"] = $params["headerId"];
		}
	
		return $this->container->renderer->render($response, 'collections.phtml', $args);
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
		$headerId = $args["headerId"];
		
		$document = CollectionHeader::find($headerId);
		if ($document != null)
		{
			$document->load("detailsCash");
			$document->load("detailsMaterials");
			$document->load("detailsThirdPartyChecks");
			$document->load("detailsTransfers");
			
			return $response->withJson([
				"Result" 	=> "OK",
				"Document" 	=> $document,
			]);
		}
		
		return $response->withJson([
			"Result"	=> "ERROR",
			"Message"	=> "No se encuentra el documento.",
		]);
	}
	
	private function read($request, $response, $args)
	{
		$pageSize       = $request->getQueryParam("jtPageSize", $default = null);
		$startIndex     = $request->getQueryParam("jtStartIndex", $default = null);
		$recordsCount   = 0;
		
		$customers_ids		= (isset($request->getParsedBody()["customers_ids"]) ? $request->getParsedBody()["customers_ids"] : null);
		$docs_types_codes	= (isset($request->getParsedBody()["docs_types_codes"]) ? $request->getParsedBody()["docs_types_codes"] : null);
		
		$records = CollectionHeader::where('project_id', $_SESSION["project_session"]->id)
								->where('is_canceled', 0)
								->when($customers_ids != null, function($query) use ($customers_ids) {
									$query->whereIn('customer_id', $customers_ids);
								})
								->when($docs_types_codes != null, function($query) use ($docs_types_codes) {
									$query->whereIn('document_type_code', $docs_types_codes);
								})
								->orderBy('dated_at', 'ASC')
								->when($pageSize != null and $startIndex != null, function($query) use ($pageSize, $startIndex) {
									$query->take($pageSize)
										->skip($startIndex);
								})
								->get();
								
		$recordsCount = CollectionHeader::where('project_id', $_SESSION["project_session"]->id)
								->where('is_canceled', 0)
								->when($customers_ids != null, function($query) use ($customers_ids) {
									$query->whereIn('customer_id', $customers_ids);
								})
								->when($docs_types_codes != null, function($query) use ($docs_types_codes) {
									$query->whereIn('document_type_code', $docs_types_codes);
								})
								->count();
									
		return $response->withJson([
			"Result" 			=> "OK",
			"Records"			=> $records,
			"TotalRecordCount"	=> $recordsCount,
		]);
	}
	
	private function create($request, $response, $params)
	{
		$body = $request->getParsedBody();
		
		// save header
		$body['project_id'] = $_SESSION["project_session"]->id;		
		$headerId = CollectionHeader::create($body)->id;
		
		// save each detail
		$detail = $body["detail"];
		foreach ($detail as $row)
		{
			$row['header_id'] = $headerId;
			
			$type = $row["type"];
			switch($type) {
				case 'cash':
					CollectionDetailCash::create($row);
					break;
					
				case 'materials':
					CollectionDetailMaterial::create($row);
					break;
					
				case 'third-party-check':
					CollectionDetailThirdPartyCheck::create($row);
					break;
					
				case 'transfer':
					CollectionDetailTransfer::create($row);
					break;
			}
		}
		
		// update document type sequence
		$docType = CollectionDocumentType::where("unique_code", $body["document_type_code"])->first();
		
		if ($docType != null)
		{
			$docNumber 		= $body["number"];
			$docSequence 	= explode("-", $docNumber)[1]; // take 2nd part of the number
			
			$int_value = ctype_digit($docSequence) ? intval($docSequence) : null;
			if ($int_value !== null)
			{
				$docType->sequence = $int_value + 1;
				$docType->save();
			}
			
			// update customer balance
			if ($docType->balance_multiplier != 0)
			{
				$_SESSION["project_session"]->updateCustomerBalance($body["customer_id"], $docType->balance_multiplier * $body["total"]);
			}
		}
		
		return $response->withJson([
			'status'	=> 'OK',
			'message'	=> 'Comprobante guardado correctamente',
		]);
	}
	
	private function update($request, $response, $params)
	{
		$body = $request->getParsedBody();
		
		// save header
		$body['project_id'] = $_SESSION["project_session"]->id;		
		$headerId = $body["id"];
		CollectionHeader::find($headerId)->update($body);
		
		CollectionDetailCash::where("header_id", $headerId)->delete();
		CollectionDetailMaterial::where("header_id", $headerId)->delete();
		CollectionDetailThirdPartyCheck::where("header_id", $headerId)->delete();
		CollectionDetailTransfer::where("header_id", $headerId)->delete();
		
		// save each detail
		$detail = $body["detail"];
		foreach ($detail as $row)
		{
			$row['header_id'] = $headerId;
			
			$type = $row["type"];
			switch($type) {
				case 'cash':
					CollectionDetailCash::create($row);
					break;
					
				case 'materials':
					CollectionDetailMaterial::create($row);
					break;
					
				case 'transfer':
					CollectionDetailTransfer::create($row);
					break;
				
				case 'third-party-check':
					CollectionDetailThirdPartyCheck::create($row);
					break;
			}
		}
		
		return $response->withJson([
			'status'	=> 'OK',
			'message'	=> 'Comprobante guardado correctamente',
		]);
	}
	
	private function remove($request, $response, $args)
	{
		$id = $request->getParsedBody()["id"];
		
		CollectionHeader::find($id)
				->update([ "is_canceled" => true ]);
		
		return $response->withJson([
			"Result" => "OK",
		]);
	}
	
	public function query($request, $response, $args)
	{
		$company = $_SESSION["company_session"];
		$company->load('customers');
		
		$project = $_SESSION["project_session"];
		$project->load('collectionsDocumentsTypes');
		
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"customers" 		=> $company->customers->sortBy("business_name"),
			"documentsTypes" 	=> $project->collectionsDocumentsTypes->sortBy("description"),
		];
	
		return $this->container->renderer->render($response, 'collections_query.phtml', $args);
	}
}