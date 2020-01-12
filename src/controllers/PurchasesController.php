<?php

namespace App\Controllers;

use App\Models\PurchaseHeader;
use App\Models\PurchaseDetail;
use App\Models\PurchaseDocumentType;
use App\Models\SupplierBalance;
 
class PurchasesController extends Controller
{
	public function __invoke($request, $response, $params)
	{	
		$company = $_SESSION["company_session"];
		$company->load('suppliers');
		$company->load('products');
		
		$project = $_SESSION["project_session"];
		$project->load('purchasesDocumentsTypes');
	
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"suppliers" 		=> $company->suppliers->sortBy("business_name"),
			"documentsTypes" 	=> $project->purchasesDocumentsTypes->sortBy("description"),
			"products" 			=> $company->products->sortBy("description"),
		];
		
		if (isset($params["headerId"]) and $params["headerId"] > 0)
		{
			$args["headerId"] = $params["headerId"];
		}
	
		return $this->container->renderer->render($response, 'purchases.phtml', $args);
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
		
		$document = PurchaseHeader::find($headerId);
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
		
		$suppliers_ids		= (isset($request->getParsedBody()["suppliers_ids"]) ? $request->getParsedBody()["suppliers_ids"] : null);
		$docs_types_codes	= (isset($request->getParsedBody()["docs_types_codes"]) ? $request->getParsedBody()["docs_types_codes"] : null);
		
		$records = PurchaseHeader::where('project_id', $_SESSION["project_session"]->id)
								->where('is_canceled', 0)
								->when($suppliers_ids != null, function($query) use ($suppliers_ids) {
									$query->whereIn('supplier_id', $suppliers_ids);
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
								
		$recordsCount = PurchaseHeader::where('project_id', $_SESSION["project_session"]->id)
								->where('is_canceled', 0)
								->when($suppliers_ids != null, function($query) use ($suppliers_ids) {
									$query->whereIn('supplier_id', $suppliers_ids);
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
		
		// validate duplicated document
		$exists = PurchaseHeader::where("supplier_id", $body["supplier_id"])
								->where("dated_at", $body["dated_at"])
								->where("total", $body["total"])
								->exists();
		if (!$exists)
		{
			// save header
			$body['project_id'] = $_SESSION["project_session"]->id;		
			$headerId = PurchaseHeader::create($body)->id;
					
			// save each detail
			$detail = $body["detail"];
			foreach ($detail as $row)
			{
				$row['header_id'] = $headerId;
				PurchaseDetail::create($row);
			}
			
			// update document type sequence
			$docType = PurchaseDocumentType::where("unique_code", $body["document_type_code"])
											->where("project_id", $body['project_id'])
											->first();
			
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
				
				// update supplier balance
				if ($docType->balance_multiplier != 0)
				{
					$_SESSION["project_session"]->updateSupplierBalance($body["supplier_id"], $docType->balance_multiplier * $body["total"]);
				}
			}
					
			return $response->withJson([
				'status'	=> 'OK',
				'message'	=> 'Comprobante guardado correctamente',
			]);
		}
		
		return $response->withJson([
			'status'	=> 'ERROR',
			'message'	=> 'Comprobante ya generado!',
		]);
	}
	
	private function update($request, $response, $params)
	{
		$body = $request->getParsedBody();
		
		// save header
		$body['project_id'] = $_SESSION["project_session"]->id;		
		$headerId = $body["id"];
		PurchaseHeader::find($headerId)->update($body);
				
		// save each detail
		PurchaseDetail::where("header_id", $headerId)->delete();
		
		$detail = $body["detail"];
		foreach ($detail as $row)
		{
			$row['header_id'] = $headerId;
			PurchaseDetail::create($row);
		}
		
		return $response->withJson([
			'status'	=> 'OK',
			'message'	=> 'Comprobante guardado correctamente',
		]);
	}
	
	private function remove($request, $response, $args)
	{
		$id = $request->getParsedBody()["id"];
		
		PurchaseHeader::find($id)
				->update([ "is_canceled" => true ]);
		
		return $response->withJson([
			"Result" => "OK",
		]);
	}
	
	public function query($request, $response, $args)
	{
		$company = $_SESSION["company_session"];
		$company->load('suppliers');
		
		$project = $_SESSION["project_session"];
		$project->load('purchasesDocumentsTypes');
		
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"suppliers" 		=> $company->suppliers->sortBy("business_name"),
			"documentsTypes" 	=> $project->purchasesDocumentsTypes->sortBy("description"),
		];
	
		return $this->container->renderer->render($response, 'purchases_query.phtml', $args);
	}
}