<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class UserProfileModel extends Authenticatable
{
    //
    
    use HasApiTokens, Notifiable;
    protected $fillable = [
        'role_id', //f - roles
        'firstName',
        'lastName',
        'email',
        'password',
        'dob',
        'mobileNumber',
        'location_id', // f- locations
        'payrate',
        'payratePercent',
        'profileImage',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'status',
        'deletedstaus',
        'deletedBy',
        'deleted_at'
    ];
    protected $table  = 'user_profiles';
    protected $hidden = ['password', 'remember_token'];

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
