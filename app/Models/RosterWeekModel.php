<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RosterWeekModel extends Model
{
    protected $table = 'roster_week';

    protected $fillable = [
        'week_start_date',
        'week_end_date',
        'created_by',
        'updated_by',
        'location_id',
        'is_published',
        'is_locked',
        'is_deleted',
        'is_active',
    ];

    // public function roster()
    // {
    //     return $this->hasMany(RosterModel::class, 'roster_week_id');
    // }

    public function userProfile(){
        return $this->belongsTo(UserProfileModel::class, 'created_by', 'id');
    }
    public function location(){
        return $this->belongsTo(LocationModel::class, 'location_id', 'id');
    }
}
