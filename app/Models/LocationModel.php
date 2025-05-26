<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationModel extends Model
{
    //
    protected $fillable = [
        'location_name',
        'latitude',
        'longitude',
        'sales',
        'address',
        'created_by',
        'updated_by',
        'status',

    ];
    protected $table = 'locations';

    public function locations()
{
    $locationIds = $this->getLocationIdsAttribute(); // explode CSV
    return LocationModel::whereIn('id', $locationIds)->get();
}
}
