<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RosterModel extends Model
{

    protected $table    = 'roster';
    protected $fillable = [
        'user_id',
        'rosterWeekId',
        'location_id',
        'date',
        'startTime',
        'endTime',
        'breakTime',
        'totalHrs',
        'hrsRate',
        'percentRate',
        'totalPay',
        'description',
        'created_by',
        'updated_by',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(UserProfileModel::class, 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(LocationModel::class, 'location_id');
    }
    public function rosterWeek(){
        return $this->belongsTo(RosterWeekModel::class, 'rosterWeekId', 'id');
    }
}
