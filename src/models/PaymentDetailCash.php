<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class PaymentDetailCash extends Model
{
	protected $table = 'payments_details_cash';
	protected $fillable = [
		'id',
		'header_id',
		'currency_code',
		'amount',
	];
	
	public function paymentHeader()
	{
		return $this->hasOne('\App\Models\PaymentHeader', 'id', 'header_id');
	}
}