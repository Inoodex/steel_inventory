<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Coil;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Warehouse::withCount(['purchases', 'sales', 'coils']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        $warehouses = $query->latest()->paginate(15)->withQueryString();

        $totalWarehouses = Warehouse::count();
        $activeWarehouses = Warehouse::where('status', 'active')->count();
        $totalCoilsStored = Coil::where('status', 'in_stock')->count();
        $totalYardWeightTon = (float) (Coil::where('status', 'in_stock')->sum('remaining_weight') / 1000);

        return view('frontend.pages.warehouses.index', compact(
            'warehouses',
            'totalWarehouses',
            'activeWarehouses',
            'totalCoilsStored',
            'totalYardWeightTon'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|max:50|unique:warehouses,code',
            'location'       => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone'  => 'nullable|string|max:50',
            'capacity_ton'   => 'nullable|numeric|min:0',
            'status'         => 'required|in:active,inactive',
            'notes'          => 'nullable|string',
        ]);

        if (empty($validated['contact_phone']) && $request->filled('phone')) {
            $validated['contact_phone'] = $request->input('phone');
        }

        if (empty($validated['code'])) {
            $count = Warehouse::count() + 1;
            $validated['code'] = 'WH-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        Warehouse::create($validated);

        return redirect()->route('warehouses.index')->with('success', 'Stockyard / Warehouse created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'location'       => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone'  => 'nullable|string|max:50',
            'capacity_ton'   => 'nullable|numeric|min:0',
            'status'         => 'required|in:active,inactive',
            'notes'          => 'nullable|string',
        ]);

        if (empty($validated['contact_phone']) && $request->filled('phone')) {
            $validated['contact_phone'] = $request->input('phone');
        }

        $warehouse->update($validated);

        return redirect()->route('warehouses.index')->with('success', 'Stockyard / Warehouse updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'Stockyard / Warehouse deleted successfully.');
    }
}
