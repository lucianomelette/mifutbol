<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
	protected $table = 'users';
	protected $fillable = [
		'id',
		'username',
		'password',
		'display_name',
	];
	
	public function companies()
    {
        return $this->belongsToMany('App\Models\Company', 'companies_users');
    }
	
	public function projects()
    {
        return $this->belongsToMany('App\Models\Project', 'projects_users');
    }
}