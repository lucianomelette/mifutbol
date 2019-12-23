<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class CollectionDetailMaterial extends Model
{
	protected $table = 'collections_details_materials';
	protected $fillable = [
		'id',
		'header_id',
		'currency_code',
		'amount',
	];
}