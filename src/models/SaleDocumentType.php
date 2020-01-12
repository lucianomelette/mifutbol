<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class SaleDocumentType extends Model
{
	protected $table = 'sales_documents_types';
	protected $fillable = [
		'id',
		'unique_code',
		'description',
		'sequence',
		'balance_multiplier',
		'project_id',
	];
}