<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class PaymentDocumentType extends Model
{
	protected $table = 'payments_documents_types';
	protected $fillable = [
		'id',
		'unique_code',
		'description',
		'sequence',
		'currency_code',
		'balance_multiplier',
		'project_id',
	];
}