<?php

namespace App\Http\Controllers;

use App\Models\Coil;
use App\Models\Lot;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class CoilController extends Controller
{
    /**
     * Display a listing of ship steel coils & plates.
     */
    public function index(Request $request)
    {
        $query = Coil::with(['lot.vendor', 'vendor', 'warehouse', 'purchase']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('coil_number', 'like', "%{$search}%")
                  ->orWhere('thickness', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('lot', function ($q) use ($search) {
                      $q->where('lot_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('lot_id')) {
            $query->where('lot_id', $request->lot_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $coils = $query->latest()->paginate(15)->withQueryString();
        $lots = Lot::where('status', 'active')->latest()->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $vendors = Vendor::where('status', '1')->orderBy('name')->get();

        // Calculate summary metrics
        $totalInStockWeight = Coil::where('status', 'in_stock')->sum('remaining_weight');
        $totalCoilsCount = Coil::count();
        $inStockCount = Coil::where('status', 'in_stock')->count();
        $totalValuation = Coil::where('status', 'in_stock')->sum('total_price');

        return view('frontend.pages.coils.index', compact(
            'coils', 
            'lots', 
            'warehouses', 
            'vendors',
            'totalInStockWeight',
            'totalCoilsCount',
            'inStockCount',
            'totalValuation'
        ));
    }

    /**
     * Update coil status (e.g. mark in_processing, exhausted, etc.)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:in_stock,in_processing,exhausted',
        ]);

        $coil = Coil::findOrFail($id);
        $coil->status = $request->status;
        $coil->save();

        return redirect()->back()->with('success', "Coil {$coil->coil_number} status updated to " . ucfirst(str_replace('_', ' ', $coil->status)));
    }
}
