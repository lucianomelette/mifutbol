<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
	protected $table = 'products';
	protected $fillable = [
		'id',
		'unique_code',
		'description',
		'company_id',
	];
}