<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class CollectionHeader extends Model
{
	protected $table = 'collections_headers';
	protected $fillable = [
		'id',
		'customer_id',
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
	
	public function customer()
	{
		return $this->hasOne('\App\Models\Customer', 'id', 'customer_id');
	}
	
	public function documentType()
	{
		return $this->hasOne('\App\Models\CollectionDocumentType', 'unique_code', 'document_type_code');
	}
	
	public function detailsCash()
	{
		return $this->hasMany('\App\Models\CollectionDetailCash', 'header_id', 'id');
	}
	
	public function detailsMaterials()
	{
		return $this->hasMany('\App\Models\CollectionDetailMaterial', 'header_id', 'id');
	}
	
	public function detailsThirdPartyChecks()
	{
		return $this->hasMany('\App\Models\CollectionDetailThirdPartyCheck', 'header_id', 'id');
	}
	
	public function detailsTransfers()
	{
		return $this->hasMany('\App\Models\CollectionDetailTransfer', 'header_id', 'id');
	}
}