<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfileModel extends Model
{
    //

    protected $fillable = [
        'role_id',//f - roles
        'first_name',
        'last_name',
        'email',
        'password',
        'mobileNumber',
        'location_id',// f- locations
        'payrate',
        'profileImage',
        'created_by',
        'created_on',
        'updated_by',
        'updated_on',
        'status',
    ];

    protected $table = 'user_profiles';

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
    public function location()
    {
        return $this->belongsTo(LocationModel::class, 'location_id', 'id');
    }
    public function unavailability()
    {
        return $this->hasMany(UnavailabilityModel::class, 'userId', 'id');
    }
}
