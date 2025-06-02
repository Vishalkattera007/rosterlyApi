<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationUsers extends Model
{
    protected $table = 'locationUsers';

    protected $fillable = [
        'location_id',
        'user_id',
        'role',
        'created_by',
        'updated_by',
    ];

    public function location()
    {
        return $this->belongsTo(LocationModel::class, 'location_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(UserProfileModel::class, 'user_id', 'id');
    }
    public function unavail(){
         return $this->hasMany(UnavailabilityModel::class, 'userId', 'user_id');
    }
}
