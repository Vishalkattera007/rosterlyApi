<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyMasterModel;
use Illuminate\Http\Request;

class CompanyMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Logic to list all companies
    }

    /**
     * Show the form for creating a new resource.
     */
    public function createCompany(Request $request)
    {
        $insertData = [
            'company_name' => $request->input('company_name'),
            'email'        => $request->input('email'),
            'phone'        => $request->input('phone'),
            'address'      => $request->input('address'),
            'city'         => $request->input('city'),
            'state'        => $request->input('state'),
            'country'      => $request->input('country'),
            'zip_code'     => $request->input('zip_code'),
            'website'      => $request->input('website'),
            'description'  => $request->input('description'),
            'is_active'    => true,
            'created_by'   => 1, 
            'updated_by'   => 1, 
        ];

        $createCompany = CompanyMasterModel::create($insertData);

        if($createCompany) {
            return response()->json([
                'message' => 'Company created successfully',
                'data'    => $createCompany,
                'status'  => true,
            ], 201);
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Logic to store a new company
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Logic to show a specific company
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Logic to show form for editing a specific company
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Logic to update a specific company
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Logic to delete a specific company
    }
}
