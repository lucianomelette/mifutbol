<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
	protected $table = 'companies';
	protected $fillable = [
		'id',
		'business_name',
		'photo_id',
	];
	
	public function customers()
    {
        return $this->hasMany('App\Models\Customer');
    }
	
	public function suppliers()
    {
        return $this->hasMany('App\Models\Supplier');
    }
	
	public function products()
    {
        return $this->hasMany('App\Models\Product');
    }
	
	public function banks()
    {
        return $this->hasMany('App\Models\Bank');
    }
    
    public function banksAccounts()
    {
        return $this->hasMany('App\Models\BankAccount');
    }
	
    public function currencies()
    {
        return $this->hasMany('App\Models\Currency');
    }
	
	public function photo()
	{
		return $this->hasOne('App\Models\Photo', 'id', 'photo_id')
					->withDefault(function($photo) {
						$photo->public_url = '/assets/repository/assets/no_photo.jpg';
					});
	}
}