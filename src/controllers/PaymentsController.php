<?php

namespace App\Controllers;

use App\Models\PaymentHeader;
use App\Models\PaymentDetailCash;
use App\Models\PaymentDetailThirdPartyCheck;
use App\Models\PaymentDetailTransfer;
use App\Models\PaymentDetailDeposit;
use App\Models\PaymentDetailOwnCheck;
use App\Models\PaymentDetailDebit;
use App\Models\PaymentDocumentType;
 
class PaymentsController extends Controller
{
	public function __invoke($request, $response, $params)
	{	
		$company = $_SESSION["company_session"];
		$company->load('suppliers');
		$company->load('banks');
		$company->load('banksAccounts');
		$company->load('currencies');
		
		$project = $_SESSION["project_session"];
		$project->load([
			'paymentsDocumentsTypes' => function($q) {
				$q->orderBy("description");
			}
		]);
	
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $_SESSION["project_session"]->full_name,
				"company_session" 	=> $company->business_name,
			],
			"suppliers" 		=> $company->suppliers->sortBy("business_name"),
			"documentsTypes" 	=> $project->paymentsDocumentsTypes,
			"banks" 			=> $company->banks->sortBy("description"),
			"banksAccounts" 	=> $company->banksAccounts,
			"currencies" 		=> $company->currencies,
		];
		
		if (isset($params["headerId"]) and $params["headerId"] > 0)
		{
			$args["headerId"] = $params["headerId"];
		}
	
		return $this->container->renderer->render($response, 'payments.phtml', $args);
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
		
		$document = PaymentHeader::find($headerId);
		if ($document != null)
		{
			$document->load("detailsCash");
			$document->load("detailsDebits");
			$document->load("detailsDeposits");
			$document->load("detailsOwnChecks");
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
		
		$suppliers_ids		= (isset($request->getParsedBody()["suppliers_ids"]) ? $request->getParsedBody()["suppliers_ids"] : null);
		$docs_types_codes	= (isset($request->getParsedBody()["docs_types_codes"]) ? $request->getParsedBody()["docs_types_codes"] : null);
		
		$records = PaymentHeader::where('project_id', $_SESSION["project_session"]->id)
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
								
		$recordsCount = PaymentHeader::where('project_id', $_SESSION["project_session"]->id)
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
		$exists = PaymentHeader::where("supplier_id", $body["supplier_id"])
								->where("dated_at", $body["dated_at"])
								->where("total", $body["total"])
								->exists();
		if (!$exists)
		{
			// save header
			$body['project_id'] = $_SESSION["project_session"]->id;		
			$headerId = PaymentHeader::create($body)->id;
			
			// save each detail
			$detail = $body["detail"];
			foreach ($detail as $row)
			{
				$row['header_id'] = $headerId;
				
				$type = $row["type"];
				switch($type) {
					case 'cash':
						PaymentDetailCash::create($row);
						break;
						
					case 'third-party-check':
						PaymentDetailThirdPartyCheck::create($row);
						break;
						
					case 'transfer':
						PaymentDetailTransfer::create($row);
						break;
						
					case 'deposit':
						PaymentDetailDeposit::create($row);
						break;
						
					case 'own-check':
						PaymentDetailOwnCheck::create($row);
						break;
						
					case 'debit':
						PaymentDetailDebit::create($row);
						break;
				}
			}
			
			// update document type sequence
			$docType = PaymentDocumentType::where("unique_code", $body["document_type_code"])->first();
			
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
		PaymentHeader::find($headerId)->update($body);
		
		PaymentDetailCash::where("header_id", $headerId)->delete();
		PaymentDetailDebit::where("header_id", $headerId)->delete();
		PaymentDetailDeposit::where("header_id", $headerId)->delete();
		PaymentDetailOwnCheck::where("header_id", $headerId)->delete();
		PaymentDetailThirdPartyCheck::where("header_id", $headerId)->delete();
		PaymentDetailTransfer::where("header_id", $headerId)->delete();
		
		// save each detail
		$detail = $body["detail"];
		foreach ($detail as $row)
		{
			$row['header_id'] = $headerId;
			
			$type = $row["type"];
			switch($type) {
				case 'cash':
					PaymentDetailCash::create($row);
					break;
					
				case 'debit':
					PaymentDetailDebit::create($row);
					break;
					
				case 'deposit':
					PaymentDetailDeposit::create($row);
					break;
					
				case 'own-check':
					PaymentDetailOwnCheck::create($row);
					break;
					
				case 'transfer':
					PaymentDetailTransfer::create($row);
					break;
				
				case 'third-party-check':
					PaymentDetailThirdPartyCheck::create($row);
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
		
		PaymentHeader::find($id)
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
		$project->load('paymentsDocumentsTypes');
		
		$args = [
			"navbar" => [
				"username_session" 	=> $_SESSION["user_session"]->username,
				"project_session" 	=> $project->full_name,
				"company_session" 	=> $company->business_name,
			],
			"suppliers" 		=> $company->suppliers->sortBy("business_name"),
			"documentsTypes" 	=> $project->paymentsDocumentsTypes->sortBy("description"),
		];
	
		return $this->container->renderer->render($response, 'payments_query.phtml', $args);
	}
}