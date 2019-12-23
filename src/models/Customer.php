<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
	protected $table = 'customers';
	protected $fillable = [
		'id',
		'unique_code',
		'fantasy_name',
		'business_name',
		'cuit',
		'category_id',
		'company_id',
	];
	
	public function category()
	{
		return $this->hasOne('\App\Models\Category', 'category_id');
	}
}