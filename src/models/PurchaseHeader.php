<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class PurchaseHeader extends Model
{
	protected $table = 'purchases_headers';
	protected $fillable = [
		'id',
		'supplier_id',
		'document_type_code',
		'number',
		'dated_at',
		'subtotal',
		'perceptions',
		'taxes',
		'total',
		'project_id',
		'is_canceled',
	];
	
	public function supplier()
	{
		return $this->hasOne('\App\Models\Supplier', 'id', 'supplier_id');
	}
	
	public function documentType()
	{
		return $this->hasOne('\App\Models\PurchaseDocumentType', 'unique_code', 'document_type_code');
	}
	
	public function details()
	{
		return $this->hasMany('\App\Models\PurchaseDetail', 'header_id');
	}
}