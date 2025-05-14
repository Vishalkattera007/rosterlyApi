<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnavailabilityModel extends Model
{
    //
    protected $fillable = [
        'userId', // f - user_profiles
        'unavailType',
        'day',
        'fromDate',
        'toDate',
        'startTime',
        'endTime',
        'notifyTo', // f - user_profiles
        'unavailStatus',
        'created_on',
        'updated_on',
        'updated_by',
    ];
    protected $table = 'unavailability';

    public function userProfile()
    {
        return $this->belongsTo(UserProfileModel::class, 'userId', 'id');
    }
    public function notifyToUserProfile()
    {
        return $this->belongsTo(UserProfileModel::class, 'notifyTo', 'id');
    }
    public function updatedByUserProfile()
    {
        return $this->belongsTo(UserProfileModel::class, 'updated_by', 'id');
    }
}
