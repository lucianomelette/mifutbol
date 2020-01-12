<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class CollectionDocumentType extends Model
{
	protected $table = 'collections_documents_types';
	protected $fillable = [
		'id',
		'unique_code',
		'description',
		'sequence',
		'currency_code',
		'balance_multiplier',
		'project_id',
	];
}