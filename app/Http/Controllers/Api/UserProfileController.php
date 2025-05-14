<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfileModel;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userProfile = UserProfileModel::all();
        return response()->json([
            'message' => 'userProfile list',
            'data'    => $userProfile,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $userRole = UserProfileModel::with('role')->where('role_id', $id)->get();
        if ($userRole->isEmpty()) {
            return response()->json([
                'message' => 'No user profiles found for this role.',
            ], 404);
        }

        return response()->json([
            'message' => 'User Profiles found',
            'role'=>$userRole->first()->role->role_name ?? 'unknown role',
            'data'    => $userRole,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
