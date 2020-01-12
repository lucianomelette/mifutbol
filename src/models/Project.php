<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
	protected $table = 'projects';
	protected $fillable = [
		'id',
		'full_name',
		'api_key',
		'photo_id',
		'company_id',
	];

	public function company()
	{
		return $this->hasOne('App\Models\Company', 'id', 'company_id');
	}
	
	public function salesDocumentsTypes()
    {
        return $this->hasMany('App\Models\SaleDocumentType');
    }
	
	public function collectionsDocumentsTypes()
    {
        return $this->hasMany('App\Models\CollectionDocumentType');
    }
	
	public function purchasesDocumentsTypes()
    {
        return $this->hasMany('App\Models\PurchaseDocumentType');
    }
	
	public function paymentsDocumentsTypes()
    {
        return $this->hasMany('App\Models\PaymentDocumentType');
    }
	
	public function photo()
	{
		return $this->hasOne('App\Models\Photo', 'id', 'photo_id')
					->withDefault(function($photo) {
						$photo->public_url = '/assets/repository/assets/no_photo.jpg';
					});
	}
	
	public function customersBalances()
	{
		return $this->hasMany('App\Models\CustomerBalance', 'project_id', 'id');
	}
	
	public function suppliersBalances()
	{
		return $this->hasMany('App\Models\SupplierBalance', 'project_id', 'id');
	}
	
	public function updateCustomerBalance($customerId, $total)
	{
		$customerBalance = $this->customersBalances()->where("customer_id", $customerId)->first();
		
		if ($customerBalance != null)
		{
			$customerBalance->balance += $total;
			$customerBalance->save();
		}
		else
		{
			$this->customersBalances()->create([
				'customer_id' 	=> $customerId,
				'balance'		=> $total,
			]);
		}
	}
	
	public function updateSupplierBalance($supplierId, $total)
	{
		$supplierBalance = $this->suppliersBalances()->where("supplier_id", $supplierId)->first();
		
		if ($supplierBalance != null)
		{
			$supplierBalance->balance += $total;
			$supplierBalance->save();
		}
		else
		{
			$this->suppliersBalances()->create([
				'supplier_id' 	=> $supplierId,
				'balance'		=> $total,
			]);
		}
	}
}