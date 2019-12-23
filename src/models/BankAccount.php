<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
	protected $table = 'banks_accounts';
	protected $fillable = [
		'id',
		'number',
		'alias',
		'bank_id',
		'company_id',
	];
	
	public function bank()
	{
		return $this->hasOne('\App\Models\Bank', 'id', 'bank_id');
	}
	
	protected $appends = [
		'display_name',
		'short_name',
	];
	
	public function getDisplayNameAttribute() {
		return $this->surname . (strlen($this->surname) > 0 ? ", " : "") . $this->full_name;
	}
	
	public function getShortNameAttribute() {
		return $this->surname . (strlen($this->surname) > 0 ? ", " : "") . substr($this->full_name, 0, 1) . ".";
	}
}