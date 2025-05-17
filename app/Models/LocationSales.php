<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationSales extends Model
{
    protected $table = 'locationsales';

    protected $fillable = [
        'location_id',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'total',
        'created_by',
        'updated_by'
    ];

    public function location()
    {
        return $this->belongsTo(LocationModel::class, 'location_id', 'id');
    }
}
