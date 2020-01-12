<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class SaleHeader extends Model
{
	protected $table = 'matches';
	protected $fillable = [
		'id',
		'dated_at',
		'white_result',
		'black_result',
		'comments',
		'is_canceled',
	];
}