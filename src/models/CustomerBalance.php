<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class CustomerBalance extends Model
{
	protected $table = 'customers_balances';
	protected $fillable = [
		'id',
		'project_id',
		'customer_id',
		'balance',
	];
}