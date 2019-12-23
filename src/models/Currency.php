<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
	protected $table = 'currencies';
	protected $fillable = [
		'id',
		'unique_code',
		'description',
		'company_id',
	];
}