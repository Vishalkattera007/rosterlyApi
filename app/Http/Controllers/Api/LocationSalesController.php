<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationSales;
use Illuminate\Http\Request;

class LocationSalesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id = null)
    {
        if ($id != null) {
            $locationSales = LocationSales::where('location_id', $id)->first();
            return response()->json($locationSales);
        } else {
            $locationSales = LocationSales::all();
            return response()->json($locationSales);
        }
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $locationSales = LocationSales::where('location_id', $id)->first();

        if (! $locationSales) {
            return response()->json(['message' => 'Location sales not found'], 404);
        }

        try {
            // Update values from request
            $locationSales->monday     = $request->monday ?? 0;
            $locationSales->tuesday    = $request->tuesday ?? 0;
            $locationSales->wednesday  = $request->wednesday ?? 0;
            $locationSales->thursday   = $request->thursday ?? 0;
            $locationSales->friday     = $request->friday ?? 0;
            $locationSales->saturday   = $request->saturday ?? 0;
            $locationSales->sunday     = $request->sunday ?? 0;
            $locationSales->updated_by = $request->updated_by;

            // Automatically calculate total
            $locationSales->total =
            $request->monday +
            $request->tuesday +
            $request->wednesday +
            $request->thursday +
            $request->friday +
            $request->saturday +
            $request->sunday;

            // Save the updated model
            $locationSales->save();

            return response()->json(['message' => 'Location sales updated successfully'], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update location sales', 'error' => $e->getMessage()], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

    }
}
