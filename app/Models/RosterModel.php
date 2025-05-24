<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RosterModel extends Model
{

    protected $table    = 'roster';
    protected $fillable = [
        'user_id',
        'location_id',
        'Date',
        'StartTime',
        'EndTime',
        'totalHrs',
        'hrsRate',
        'percentRate',
        'totalPay',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(LocationModel::class, 'location_id');
    }
}
