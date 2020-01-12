<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class CollectionDetailThirdPartyCheck extends Model
{
	protected $table = 'collections_details_third_party_checks';
	protected $fillable = [
		'id',
		'header_id',
		'bank_id',
		'number',
		'owner',
		'expiration_at',
		'amount',
	];
	
	public function bank()
	{
	    return $this->hasOne('\App\Models\Bank', 'id', 'bank_id');
	}
}