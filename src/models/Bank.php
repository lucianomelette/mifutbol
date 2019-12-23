<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
	protected $table = 'banks';
	protected $fillable = [
		'id',
		'unique_code',
		'description',
		'company_id',
	];
}