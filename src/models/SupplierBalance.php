<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class SupplierBalance extends Model
{
	protected $table = 'suppliers_balances';
	protected $fillable = [
		'id',
		'project_id',
		'supplier_id',
		'balance',
	];
}