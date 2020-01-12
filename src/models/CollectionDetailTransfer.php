<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class CollectionDetailTransfer extends Model
{
	protected $table = 'collections_details_transfers';
	protected $fillable = [
		'id',
		'header_id',
		'bank_account_id',
		'cbu',
		'number',
		'dated_at',
		'amount',
	];
}