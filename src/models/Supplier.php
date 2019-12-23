<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
	protected $table = 'suppliers';
	protected $fillable = [
		'id',
		'unique_code',
		'fantasy_name',
		'business_name',
		'cuit',
		'category_id',
		'company_id',
	];
	
	public function category()
	{
		return $this->hasOne('\App\Models\Category', 'category_id');
	}
	
	public function contacts()
	{
		return $this->belongsToMany('\App\Models\Contact');
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