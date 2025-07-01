<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationModel;
use App\Models\LocationSales;
use App\Models\LocationUsers;
use App\Models\UserProfileModel;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            $loginId = $request->user('api')->id;
            $roleId  = $request->user('api')->role_id;

            if ($roleId === 1) {
                $locations = LocationModel::all();
            } else {
                $location_ids = LocationUsers::where('user_id', $loginId)->pluck('location_id');
                $locations    = LocationModel::whereIn('id', $location_ids)->get();
            }

            // $locations = LocationModel::all();
            return response()->json($locations);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to fetch locations', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $locationSales      = $request->sales;
        $totalLocationSales = $locationSales * 7;
        $createdBy          = $request->created_by;
        try {
            $location = LocationModel::create([
                'location_name' => $request->location_name,
                'latitude'      => $request->latitude,
                'longitude'     => $request->longitude,
                'address'       => $request->address,
                'sales'         => $locationSales,
                'created_by'    => $createdBy,
            ]);

            $locationId = $location->id;

            if ($locationSales != 0) {
                $insertLocationSales = LocationSales::create([
                    'location_id' => $locationId,
                    'monday'      => $locationSales,
                    'tuesday'     => $locationSales,
                    'wednesday'   => $locationSales,
                    'thursday'    => $locationSales,
                    'friday'      => $locationSales,
                    'saturday'    => $locationSales,
                    'sunday'      => $locationSales,
                    'total'       => $totalLocationSales,
                    'created_by'  => $createdBy,
                ]);
                if (! $insertLocationSales) {
                    return response()->json(['message' => 'Failed to create location sales'], 500);
                }
            }

            return response()->json(['message' => 'Location created successfully', 'data' => $location], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to create location', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $location = LocationModel::findOrFail($id);
            return response()->json($location);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Location not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to retrieve location', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $location = LocationModel::findOrFail($id);

            $location->update([
                'location_name' => $request->location_name,
                'latitude'      => $request->latitude,
                'longitude'     => $request->longitude,
                'address'       => $request->address,
                'updated_by'    => $request->updated_by,
                'status'        => $request->status,
            ]);

            return response()->json(['message' => 'Location updated successfully', 'data' => $location]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Location not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to update location', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $location = LocationModel::find($id);

        if (! $location) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found.',
            ], 404);
        }

        // Update status to 0 (inactive)
        $location->status = 0;
        $location->save();

        return response()->json([
            'success' => true,
            'message' => 'Location Inacive successfully.',
        ]);
    }

    // In your Controller
// public function getUserIdsByLocation($locationId, Request $request)
// {
//     try {
//         $userIds = LocationUsers::where('location_id', $locationId)->get();

//         return response()->json([
//             'status'   => true,
//             'user_ids' => $userIds,
//         ]);
//     } catch (Exception $e) {
//         return response()->json([
//             'status'  => false,
//             'message' => 'Failed to fetch user IDs',
//             'error'   => $e->getMessage(),
//         ], 500);
//     }
// }

    // fetch employee based on location id
    public function getUsersByLocation($locationId, Request $request)
    {
        try {
            $loggedInUser = $request->user('api');

            $fetchRole = $loggedInUser->role_id;
            $pageName  = $request->pageName;

            if ($fetchRole === 1) {
                $users = LocationUsers::with(['user',
                    'unavail' => function ($q) {
                        $q->where('unavailStatus', 1);
                    },
                ])->where('location_id', $locationId)->get();
            } else {

                $getLoggedInUserLocation = LocationUsers::where('user_id', $loggedInUser->id)
                    ->pluck('location_id');

                if ($pageName == "rosters") {
                    $users = LocationUsers::with([
                        'user',
                        'unavail' => function ($q) {
                            $q->where('unavailStatus', 1);
                        },
                    ])
                        ->whereIn('location_id', $getLoggedInUserLocation)
                        ->whereHas('user', function ($q) {
                            $q->where('role_id', 3); // ✅ Only fetch users with role_id = 3
                        })
                        ->get()
                        ->unique('user_id')
                        ->values();
                } else {
                    $users = LocationUsers::with(['user',
                        'unavail' => function ($q) {
                            $q->where('unavailStatus', 1);
                        },
                    ])->whereIn('location_id', $getLoggedInUserLocation)->get()->unique('user_id')->values();
                }

            }

            // $users = $query->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'No users found for this location.',
                    'status'  => false,
                ], 404);
            }

            return response()->json([
                'message' => 'Users found for the given location.',
                'data'    => $users,
                'role'    => $fetchRole,
                'status'  => true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch users by location.',
                'error'   => $e->getMessage(),
                'status'  => false,
            ], 500);
        }
    }

    public function postUsersinLocation(Request $request, $locationId)
    {
        try {
            $location = LocationModel::findOrFail($locationId);

            if (! $location) {
                return response()->json(['message' => 'Location not found'], 404);
            }

            $userIds = $request->input('user_ids', []);

            if (empty($userIds)) {
                return response()->json(['message' => 'No user IDs provided'], 400);
            }

            foreach ($userIds as $userId) {
                $user    = UserProfileModel::find($userId);
                $loginId = $request->user('api')->id;
                if ($user) {
                    // Check if already assigned
                    $exists = LocationUsers::where('user_id', $userId)
                        ->where('location_id', $locationId)
                        ->exists();

                    if (! $exists) {
                        LocationUsers::create([
                            'user_id'     => $userId,
                            'location_id' => $locationId,
                            'role'        => $user->role_id,
                            'created_by'  => $loginId,
                            // 'updated_by'  => Auth::id(),
                        ]);
                    }
                                                                            // Get role name (assuming you have a relationship or mapping)
                    $roleName        = $user->role->role_name ?? 'Unknown'; // or use RoleModel::find($user->role_id)->name
                    $assignedRoles[] = $roleName;
                }
                // Append role name to the list

            }

            return response()->json([
                'message' => implode(', ', $assignedRoles) . ' assigned to location successfully',
            ], 200);

        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to assign users to location', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateUsersLocation(Request $request, $locationId)
    {
        try {
            $location = LocationModel::findOrFail($locationId);

            if (! $location) {
                return response()->json(['message' => 'Location not found'], 404);
            }

            $userIds = $request->input('user_ids', []);
            $loginId = $request->user('api')->id;

            if (empty($userIds)) {
                return response()->json(['message' => 'No user IDs provided'], 400);
            }

            // Delete existing user-location assignments for this location
            LocationUsers::where('location_id', $locationId)->delete();

            // Reassign with new user list
            foreach ($userIds as $userId) {
                $user = UserProfileModel::find($userId);

                if ($user) {
                    LocationUsers::create([
                        'user_id'     => $userId,
                        'location_id' => $locationId,
                        'role'        => $user->role_id,
                        'updated_by'  => $loginId,
                    ]);
                }
            }

            return response()->json(['message' => 'Users for the location updated successfully'], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update users for the location',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteUserFromLocation(Request $request, $locationId)
    {
        try {
            $userIds = $request->input('user_ids', []);

            if (empty($userIds)) {
                return response()->json(['message' => 'No user IDs provided'], 400);
            }

            // Get unique role names from location_users -> roles
            $roleNames = DB::table('locationUsers')
                ->join('roles', 'locationUsers.role', '=', 'roles.id')
                ->where('locationUsers.location_id', $locationId)
                ->whereIn('locationUsers.user_id', $userIds)
                ->pluck('roles.role_name')
                ->unique()
                ->toArray();

            // Delete users from location
            LocationUsers::where('location_id', $locationId)
                ->whereIn('user_id', $userIds)
                ->delete();

            $rolesText = implode(', ', $roleNames);
            $message   = count($roleNames) > 1
            ? "{$rolesText} removed from location successfully"
            : "{$rolesText} removed from location successfully";

            return response()->json(['message' => $message], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to remove users from location',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
