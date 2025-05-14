<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    //

    protected $fillable = [
        'role_name',
    ];

    protected $table = 'roles';
    public function userProfiles()
    {
        return $this->hasMany(UserProfileModel::class, 'role_id', 'id');
    }
   

}
