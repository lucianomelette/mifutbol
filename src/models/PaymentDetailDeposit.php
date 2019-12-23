<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class PaymentDetailDeposit extends Model
{
	protected $table = 'payments_details_deposits';
	protected $fillable = [
		'id',
		'header_id',
		'bank_account_id',
		'number',
		'owner',
		'dated_at',
		'amount',
	];
}