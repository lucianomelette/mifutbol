<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class CollectionDetailCash extends Model
{
	protected $table = 'collections_details_cash';
	protected $fillable = [
		'id',
		'header_id',
		'currency_code',
		'amount',
	];
	
	public function collectionHeader()
	{
		return $this->hasOne('\App\Models\CollectionHeader', 'id', 'header_id');
	}
}