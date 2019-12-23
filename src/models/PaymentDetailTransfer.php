<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class PaymentDetailTransfer extends Model
{
	protected $table = 'payments_details_transfers';
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