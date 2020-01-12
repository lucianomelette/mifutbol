<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class SaleHeader extends Model
{
	protected $table = 'sales_headers';
	protected $fillable = [
		'id',
		'customer_id',
		'document_type_code',
		'number',
		'dated_at',
		'subtotal',
		'perceptions',
		'taxes',
		'total',
		'comments',
		'project_id',
		'is_canceled',
	];
	
	public function customer()
	{
		return $this->hasOne('\App\Models\Customer', 'id', 'customer_id');
	}
	
	public function documentType()
	{
		return $this->hasOne('\App\Models\SaleDocumentType', 'unique_code', 'document_type_code');
	}
	
	public function details()
	{
		return $this->hasMany('\App\Models\SaleDetail', 'header_id');
	}
}