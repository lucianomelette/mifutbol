<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
	protected $table = 'exchange';
	protected $fillable = [
		'id',
		'dated_at',
		'currency_code',
		'price',
		'company_id',
	];
	
}