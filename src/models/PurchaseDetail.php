<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
	protected $table = 'purchases_details';
	protected $fillable = [
		'id',
		'header_id',
		'product_id',
		'product_description',
		'quantity',
		'unit_price',
		'subtotal',
		'tax',
		'total',
	];
}