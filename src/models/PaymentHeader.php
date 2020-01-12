<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class PaymentHeader extends Model
{
	protected $table = 'payments_headers';
	protected $fillable = [
		'id',
		'supplier_id',
		'document_type_code',
		'number',
		'dated_at',
		'comments',
		'exchange',
		'withholdings',
		'total',
		'project_id',
		'is_canceled',
	];

	public function project()
	{
		return $this->hasOne('\App\Models\Project', 'id', 'project_id');
	}
	
	public function supplier()
	{
		return $this->hasOne('\App\Models\Supplier', 'id', 'supplier_id');
	}
	
	public function documentType()
	{
		return $this->hasOne('\App\Models\PaymentDocumentType', 'unique_code', 'document_type_code');
	}
	
	public function detailsCash()
	{
		return $this->hasMany('\App\Models\PaymentDetailCash', 'header_id', 'id');
	}
	
	public function detailsDebits()
	{
		return $this->hasMany('\App\Models\PaymentDetailDebit', 'header_id', 'id');
	}
	
	public function detailsDeposits()
	{
		return $this->hasMany('\App\Models\PaymentDetailDeposit', 'header_id', 'id');
	}
	
	public function detailsOwnChecks()
	{
		return $this->hasMany('\App\Models\PaymentDetailOwnCheck', 'header_id', 'id');
	}
	
	public function detailsThirdPartyChecks()
	{
		return $this->hasMany('\App\Models\PaymentDetailThirdPartyCheck', 'header_id', 'id');
	}
	
	public function detailsTransfers()
	{
		return $this->hasMany('\App\Models\PaymentDetailTransfer', 'header_id', 'id');
	}
}