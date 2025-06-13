<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RosterAttendanceLog extends Model
{
    use HasFactory;

    protected $table = 'roster_attendance_logs';

    protected $fillable = [
        'user_id',
        'roster_id',
        'action_type',
        'timestamp',
        'location',
        'remarks',
    ];

    public function user()
    {
        return $this->belongsTo(UserProfileModel::class, 'user_id');
    }

    public function roster()
    {
        return $this->belongsTo(RosterModel::class, 'roster_id');
    }
}
