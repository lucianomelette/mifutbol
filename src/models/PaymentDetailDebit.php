<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class PaymentDetailDebit extends Model
{
	protected $table = 'payments_details_debits';
	protected $fillable = [
		'id',
		'header_id',
		'bank_account_id',
		'amount',
	];
}