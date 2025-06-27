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
        'company_id', // f - company_master
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'status',
        'deletedstaus',
        'deletedBy',
        'deleted_at',
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

    public function locationUsers(){
        return $this->belongsTo(LocationUsers::class, 'id', 'user_id');
    }
    public function unavailability()
    {
        return $this->hasMany(UnavailabilityModel::class, 'userId', 'id');
    }

    // Get locations as array
    // Accessor to get location IDs as array
    public function getLocationIdsAttribute()
    {
        return $this->location_id ? explode(',', $this->location_id) : [];
    }

// Method to add new locations
    public function addLocations(array $newLocationIds)
    {
        $existing          = $this->location_ids; // uses accessor above
        $merged            = array_unique(array_merge($existing, $newLocationIds));
        $this->location_id = implode(',', $merged);
        $this->save();
    }

    // Get Location models for assigned location IDs
    public function locations()
    {
        $locationIds = $this->getLocationIdsAttribute();
        return LocationModel::whereIn('id', $locationIds)->get();
    }

}
