<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RosterTimesheet extends Model
{
    use HasFactory;

    protected $table = 'roster_timesheets';

    protected $fillable = [
        'user_id',
        'roster_id',
        'date',
        'total_work_minutes',
        'total_break_minutes',
    ];

    // Relationships (optional, if you want access to user/roster info)
    public function user()
    {
        return $this->belongsTo(UserProfileModel::class, 'user_id');
    }

    public function roster()
    {
        return $this->belongsTo(RosterModel::class, 'roster_id');
    }
}
