<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all();
        return response()->json([
            'message' => 'Roles list',
            'data' => $roles
        ]);

        // return response()->json([
        //     'message'=>'Roles list'
        // ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    try {
        $role = new Role();
        $role->role_name  = $request->role_name ;
        $role->save();

        return response()->json([
            'status' => true,
            'message' => 'Role created successfully',
            'data' => $role
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to create role',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Display the specified resource.
     */
     public function show($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Role details',
            'data' => $role
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json([
                    'status' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $role->role_name = $request->role_name;
            $role->save();

            return response()->json([
                'status' => true,
                'message' => 'Role updated successfully',
                'data' => $role
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update role',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json([
                    'status' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $role->delete();

            return response()->json([
                'status' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete role',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
