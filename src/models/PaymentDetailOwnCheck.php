<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class PaymentDetailOwnCheck extends Model
{
	protected $table = 'payments_details_own_checks';
	protected $fillable = [
		'id',
		'header_id',
		'bank_account_id',
		'number',
		'dated_at',
		'expiration_at',
		'amount',
	];

	public function bankAccount()
	{
		return $this->hasOne('\App\Models\BankAccount', 'id', 'bank_account_id');
	}
}