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

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $locations = LocationModel::all();
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

    // fetch employee based on location id
    public function getUsersByLocation($locationId, Request $request)
    {
        try {
            $loggedInUser = $request->user('api');
            $query        = LocationUsers::with(['user',
                'unavail' => function ($q) {
                    $q->where('unavailStatus', 1);
                },
            ])->where('location_id', $locationId)->where('created_by', $loggedInUser->id);

            $users = $query->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'No users found for this location.',
                    'status'  => false,
                ], 404);
            }

            return response()->json([
                'message' => 'Users found for the given location.',
                'data'    => $users,
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

    // public function getRolesByLocationId($locationId, $roleId)
    // {
    //     try {
    //         $users = UserProfileModel::with('location')
    //             ->whereRaw("FIND_IN_SET(?, location_id)", [$locationId])
    //             ->where('role_id', $roleId)
    //             ->get();

    //         return response()->json([
    //             'message' => $users->isEmpty() ? 'No users found.' : 'Users fetched successfully.',
    //             'data'    => $users,
    //             'status'  => true,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error fetching users.',
    //             'error'   => $e->getMessage(),
    //             'status'  => false,
    //         ], 500);
    //     }
    // }

    // public function assignLocationToEmployees(Request $request)
    // {
    //     try {
    //         $locationId     = $request->location_id;
    //         $duplicateUsers = [];
    //         $assignedUsers  = [];

    //         foreach ($request->employee_ids as $employeeId) {
    //             $user = UserProfileModel::find($employeeId);

    //             if (! $user) {
    //                 continue; // Optionally collect missing users
    //             }

    //             $existingLocations = $user->location_ids;

    //             if (in_array($locationId, $existingLocations)) {
    //                 $duplicateUsers[] = $user->firstName . ' ' . $user->lastName;
    //                 continue;
    //             }

    //             $user->addLocations([$locationId]);
    //             $assignedUsers[] = $user->id;
    //         }

    //         return response()->json([
    //             'status'             => true,
    //             'message'            => count($duplicateUsers)
    //             ? 'Some Employee were already assigned to this location.'
    //             : 'All Employee assigned successfully.',
    //             'already_assigned'   => $duplicateUsers,
    //             'newly_assigned_ids' => $assignedUsers,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'An error occurred while assigning employees.',
    //             'error'   => $e->getMessage(), // For debugging, remove in production
    //         ], 500);
    //     }
    // }

    // public function getEmployeesByLocation($location_id)
    // {
    //     try {
    //         $locationId = (string) $location_id;

    //         $employees = UserProfileModel::where(function ($query) use ($locationId) {
    //             $query->where('location_id', $locationId)
    //                 ->orWhere('location_id', 'LIKE', $locationId . ',%')
    //                 ->orWhere('location_id', 'LIKE', '%,' . $locationId . ',%')
    //                 ->orWhere('location_id', 'LIKE', '%,' . $locationId);
    //         })->get();

    //         return response()->json([
    //             'status'    => true,
    //             'message'   => 'Employees fetched successfully.',
    //             'employees' => $employees,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Error fetching employees.',
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // onelocation - multiple users

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
                }
            }

            return response()->json(['message' => 'Users assigned to location successfully'], 200);

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

            // Delete all matching records in one query
            LocationUsers::where('location_id', $locationId)
                ->whereIn('user_id', $userIds)
                ->delete();

            return response()->json(['message' => 'Users removed from location successfully'], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to remove users from location',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
