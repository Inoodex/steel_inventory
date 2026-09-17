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
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $warehouse = Warehouse::withCount(['purchases', 'sales', 'coils'])->findOrFail($id);

        // Coils currently located at this warehouse
        $coilsQuery = $warehouse->coils()->with(['vendor', 'lot', 'purchase'])->latest();

        if ($request->filled('coil_status')) {
            $coilsQuery->where('status', $request->coil_status);
        }

        $coils = $coilsQuery->paginate(15, ['*'], 'coils_page')->withQueryString();

        // Key yard inventory analytics
        $inStockCoilsCount = $warehouse->coils()->where('status', 'in_stock')->count();
        $inProcessingCoilsCount = $warehouse->coils()->where('status', 'processing')->count();
        $totalStockTonnageKg = (float) $warehouse->coils()->whereIn('status', ['in_stock', 'processing'])->sum('remaining_weight');
        $totalStockTonnageMT = $totalStockTonnageKg / 1000;
        
        $totalStockValuation = (float) $warehouse->coils()->whereIn('status', ['in_stock', 'processing'])->get()->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);

        $capacityTon = (float) ($warehouse->capacity_ton ?? 0);
        $utilizationPercent = $capacityTon > 0 ? min(100, round(($totalStockTonnageMT / $capacityTon) * 100, 1)) : 0;

        // Thickness-wise Weighted Average Cost & Stock Breakdown
        $activeCoils = $warehouse->coils()
            ->whereIn('status', ['in_stock', 'processing'])
            ->where('remaining_weight', '>', 0)
            ->get();

        $thicknessBreakdown = $activeCoils->groupBy(function ($coil) {
            return trim($coil->thickness) ?: 'Standard';
        })->map(function ($group, $thickness) {
            $totalCoils = $group->count();
            $totalPieces = $group->sum(fn($c) => (float)($c->piece_count ?: 1));
            $totalRemainingWeight = (float) $group->sum('remaining_weight');
            $totalValuation = (float) $group->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);
            $avgPricePerKg = $totalRemainingWeight > 0 ? ($totalValuation / $totalRemainingWeight) : 0;
            $avgPricePerTon = $avgPricePerKg * 1000;
            $minRate = (float) $group->min('rate_per_ton');
            $maxRate = (float) $group->max('rate_per_ton');
            $sizes = $group->map(function($c) {
                $w = $c->width ?: '';
                $l = ($c->length && $c->length !== 'N/A') ? $c->length : '';
                return trim("{$w} {$l}");
            })->filter()->unique()->values()->all();

            return [
                'thickness' => $thickness,
                'coils_count' => $totalCoils,
                'pieces_count' => $totalPieces,
                'total_weight' => $totalRemainingWeight,
                'total_weight_mt' => $totalRemainingWeight / 1000,
                'total_valuation' => $totalValuation,
                'avg_price_per_kg' => $avgPricePerKg,
                'avg_price_per_ton' => $avgPricePerTon,
                'min_rate' => $minRate,
                'max_rate' => $maxRate,
                'sizes' => $sizes,
            ];
        })->sortByDesc('total_weight');

        // Recent purchases received at this warehouse
        $recentPurchases = $warehouse->purchases()->with(['vendor', 'lot'])->latest()->take(10)->get();

        // Recent sales dispatched from this warehouse
        $recentSales = $warehouse->sales()->with('customer')->latest()->take(10)->get();

        return view('frontend.pages.warehouses.show', compact(
            'warehouse',
            'coils',
            'inStockCoilsCount',
            'inProcessingCoilsCount',
            'totalStockTonnageMT',
            'totalStockTonnageKg',
            'totalStockValuation',
            'capacityTon',
            'utilizationPercent',
            'thicknessBreakdown',
            'recentPurchases',
            'recentSales'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $warehouse = Warehouse::withCount(['purchases', 'sales', 'coils'])->findOrFail($id);
        return view('frontend.pages.warehouses.edit', compact('warehouse'));
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

        return redirect()->route('warehouses.show', $warehouse->id)->with('success', 'Stockyard / Warehouse updated successfully.');
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
