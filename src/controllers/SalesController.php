<?php

namespace App\Controllers;

use App\Models\SaleHeader;
use App\Models\SaleDetail;
use App\Models\SaleDocumentType;
 
class SalesController extends Controller
{
	public function __invoke($request, $response, $params)
	{	
		$company = $_SESSION["company_session"];
		$company->load('customers');
		$company->load('products');
		
		$project = $_SESSION["project_session"];
		$project->load('salesDocumentsTypes');
	
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"customers" 		=> $company->customers->sortBy("business_name"),
			"documentsTypes" 	=> $project->salesDocumentsTypes->sortBy("description"),
			"products" 			=> $company->products->sortBy("description"),
		];
		
		if (isset($params["headerId"]) and $params["headerId"] > 0)
		{
			$args["headerId"] = $params["headerId"];
		}
	
		return $this->container->renderer->render($response, 'sales.phtml', $args);
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
		
		$document = SaleHeader::find($headerId);
		if ($document != null)
		{
			$document->load("details");
			
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
		
		$records = SaleHeader::where('project_id', $_SESSION["project_session"]->id)
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
								
		$recordsCount = SaleHeader::where('project_id', $_SESSION["project_session"]->id)
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
		$headerId = SaleHeader::create($body)->id;
				
		// save each detail
		$detail = $body["detail"];
		foreach ($detail as $row)
		{
			$row['header_id'] = $headerId;
			SaleDetail::create($row);
		}
		
		// update document type sequence
		$docType = SaleDocumentType::where("unique_code", $body["document_type_code"])->first();
		
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
		SaleHeader::find($headerId)->update($body);
				
		// save each detail
		SaleDetail::where("header_id", $headerId)->delete();
		
		$detail = $body["detail"];
		foreach ($detail as $row)
		{
			$row['header_id'] = $headerId;
			SaleDetail::create($row);
		}
		
		return $response->withJson([
			'status'	=> 'OK',
			'message'	=> 'Comprobante guardado correctamente',
		]);
	}
	
	private function remove($request, $response, $args)
	{
		$id = $request->getParsedBody()["id"];
		
		SaleHeader::find($id)
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
		$project->load('salesDocumentsTypes');
		
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"customers" 		=> $company->customers->sortBy("bussiness_name"),
			"documentsTypes" 	=> $project->salesDocumentsTypes->sortBy("description"),
		];
	
		return $this->container->renderer->render($response, 'sales_query.phtml', $args);
	}
}