<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Match extends Model
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