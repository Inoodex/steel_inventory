<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LotController extends Controller
{
    /**
     * Display a listing of lots.
     */
    public function index(Request $request)
    {
        $query = Lot::with(['vendor', 'purchases']);

        // Search by Lot Number or Vendor Name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Date Range
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('lot_date', [$request->from, $request->to]);
        }

        $lots = $query->latest()->paginate(15)->withQueryString();
        $vendors = Vendor::latest()->get();

        // Calculate summary metrics
        $totalLots = Lot::count();
        $activeLots = Lot::where('status', 'active')->count();
        $totalWeight = (float) \App\Models\Purchase::whereNotNull('lot_id')->sum('total_weight');
        $totalValuation = (float) \App\Models\Purchase::whereNotNull('lot_id')->sum('total_price');

        return view('frontend.pages.lots.index', compact('lots', 'vendors', 'totalLots', 'activeLots', 'totalWeight', 'totalValuation'));
    }

    /**
     * Store a newly created lot in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lot_number' => 'nullable|string|max:100|unique:lots,lot_number',
            'vendor_id'  => 'required|exists:vendors,id',
            'lot_date'   => 'required|date',
            'notes'      => 'nullable|string',
            'status'     => 'required|in:active,closed',
        ]);

        if (empty($validated['lot_number'])) {
            $validated['lot_number'] = Lot::generateLotNumber();
        }

        $validated['created_by'] = Auth::id();

        $lot = Lot::create($validated);

        return redirect()->back()->with('success', "Lot '{$lot->lot_number}' created successfully.");
    }

    /**
     * Quick create lot via AJAX (used inside Purchase form modal)
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'lot_number' => 'nullable|string|max:100|unique:lots,lot_number',
            'vendor_id'  => 'required|exists:vendors,id',
            'lot_date'   => 'required|date',
            'notes'      => 'nullable|string',
        ]);

        if (empty($validated['lot_number'])) {
            $validated['lot_number'] = Lot::generateLotNumber();
        }

        $validated['status'] = 'active';
        $validated['created_by'] = Auth::id();

        $lot = Lot::create($validated);
        $lot->load('vendor');

        return response()->json([
            'success' => true,
            'message' => "Lot '{$lot->lot_number}' created successfully.",
            'lot'     => [
                'id'         => $lot->id,
                'lot_number' => $lot->lot_number,
                'vendor_id'  => $lot->vendor_id,
                'vendor_name'=> $lot->vendor ? $lot->vendor->name : '',
            ],
        ]);
    }

    /**
     * Display detailed summary of a specific lot.
     */
    public function show(string $id)
    {
        $lot = Lot::with(['vendor', 'purchases.coils', 'purchases.vendor', 'creator'])->findOrFail($id);

        $totalPurchases = $lot->purchases->count();
        $totalQuantity  = $lot->purchases->sum('quantity');
        $totalAmount    = $lot->purchases->sum('total_price');
        $totalPaid      = $lot->purchases->sum('payment');
        $totalDue       = $lot->purchases->sum('due');

        return view('frontend.pages.lots.show', compact(
            'lot', 'totalPurchases', 'totalQuantity', 'totalAmount', 'totalPaid', 'totalDue'
        ));
    }

    /**
     * Update the specified lot in storage.
     */
    public function update(Request $request, string $id)
    {
        $lot = Lot::findOrFail($id);

        $validated = $request->validate([
            'lot_number' => 'required|string|max:100|unique:lots,lot_number,' . $lot->id,
            'vendor_id'  => 'required|exists:vendors,id',
            'lot_date'   => 'required|date',
            'notes'      => 'nullable|string',
            'status'     => 'required|in:active,closed',
        ]);

        $validated['updated_by'] = Auth::id();

        $lot->update($validated);

        return redirect()->back()->with('success', "Lot '{$lot->lot_number}' updated successfully.");
    }

    /**
     * Remove the specified lot from storage.
     */
    public function destroy(string $id)
    {
        $lot = Lot::findOrFail($id);
        
        if ($lot->purchases()->count() > 0) {
            return redirect()->back()->with('error', "Cannot delete Lot '{$lot->lot_number}' because it has linked purchases.");
        }

        $lot->delete();

        return redirect()->back()->with('success', "Lot deleted successfully.");
    }
}
