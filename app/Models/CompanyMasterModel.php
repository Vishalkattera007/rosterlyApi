<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyMasterModel extends Model
{
    //
    protected $table    = 'company_master';
    protected $fillable = [
        'company_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'website',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function companyUsers()
    {
        return $this->hasMany(UserProfileModel::class, 'company_id', 'id');
    }
}
