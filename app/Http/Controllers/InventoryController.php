<?php

namespace App\Http\Controllers;

use App\Models\Coil;
use App\Models\Lot;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    /**
     * Show opening stock batch entry page.
     */
    public function openingStock()
    {
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $lots = Lot::where('status', 'active')->latest()->get();
        $vendors = Vendor::where('status', '1')->orderBy('name')->get();

        return view('frontend.pages.inventory.opening_stock', compact('warehouses', 'lots', 'vendors'));
    }

    /**
     * Store opening stock intake (single modal or multi-row batch grid).
     */
    public function storeOpeningStock(Request $request)
    {
        if ($request->has('items') && is_array($request->items)) {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.warehouse_id' => 'required|exists:warehouses,id',
                'items.*.thickness' => 'required',
                'items.*.width' => 'required',
                'items.*.length' => 'required',
                'items.*.piece_count' => 'required|numeric|min:0.01',
                'items.*.net_weight' => 'required|numeric|min:0.01',
                'items.*.rate_per_ton' => 'nullable|numeric|min:0',
                'items.*.lot_id' => 'nullable|exists:lots,id',
                'items.*.vendor_id' => 'nullable|exists:vendors,id',
            ], [
                'items.*.warehouse_id.required' => 'Warehouse is required for each row.',
                'items.*.thickness.required' => 'Thickness is required for each row.',
                'items.*.width.required' => 'Width / Size is required for each row.',
                'items.*.length.required' => 'Length / Size type is required for each row.',
                'items.*.piece_count.required' => 'Piece count is required for each row.',
                'items.*.net_weight.required' => 'Net weight is required for each row.',
            ]);

            $rows = $request->items;
        } else {
            $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'thickness' => 'required',
                'width' => 'required',
                'length' => 'required',
                'piece_count' => 'required|numeric|min:0.01',
                'net_weight' => 'required|numeric|min:0.01',
                'rate_per_ton' => 'nullable|numeric|min:0',
                'lot_id' => 'nullable|exists:lots,id',
                'vendor_id' => 'nullable|exists:vendors,id',
            ]);

            $rows = [$request->all()];
        }

        $createdCount = 0;
        $totalWeight = 0;
        $totalValuation = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $item) {
                if (empty($item['thickness']) && empty($item['net_weight'])) {
                    continue;
                }

                $netWeight = (float) ($item['net_weight'] ?? 0);
                $pieceCount = (float) ($item['piece_count'] ?? 1);
                $rate = (float) ($item['rate_per_ton'] ?? 0);
                $itemTotal = $netWeight * $rate;

                $customCoilNo = !empty($item['coil_number']) ? trim($item['coil_number']) : null;
                if ($customCoilNo && Coil::where('coil_number', $customCoilNo)->exists()) {
                    $coilNumber = Coil::generateCoilNumber();
                } else {
                    $coilNumber = $customCoilNo ?: Coil::generateCoilNumber();
                }

                Coil::create([
                    'coil_number'      => $coilNumber,
                    'purchase_id'      => null, // Opening Stock has no vendor purchase invoice
                    'lot_id'           => !empty($item['lot_id']) ? $item['lot_id'] : null,
                    'vendor_id'        => !empty($item['vendor_id']) ? $item['vendor_id'] : null,
                    'warehouse_id'     => $item['warehouse_id'],
                    'thickness'        => $item['thickness'],
                    'width'            => $item['width'],
                    'length'           => $item['length'],
                    'piece_count'      => $pieceCount,
                    'gross_weight'     => $netWeight,
                    'tare_weight'      => 0,
                    'net_weight'       => $netWeight,
                    'remaining_weight' => $netWeight,
                    'rate_per_ton'     => $rate,
                    'total_price'      => $itemTotal,
                    'status'           => 'in_stock',
                    'notes'            => !empty($item['notes']) ? $item['notes'] : 'Opening Stock Intake',
                    'created_by'       => Auth::id(),
                ]);

                $createdCount++;
                $totalWeight += $netWeight;
                $totalValuation += $itemTotal;
            }

            // Record journal entry for stock asset valuation if double entry exists
            if ($totalValuation > 0 && function_exists('createJournalEntry')) {
                try {
                    $invAccount = \App\Models\ChartOfAccount::where('account_code', '1140')->first();
                    $eqAccount = \App\Models\ChartOfAccount::where('account_code', '3100')->first();

                    if ($invAccount && $eqAccount) {
                        createJournalEntry([
                            'entry_date' => now()->format('Y-m-d'),
                            'reference_type' => 'opening_stock',
                            'description' => "Opening Stock Intake ({$createdCount} items, " . number_format($totalWeight, 2) . " kg)",
                            'items' => [
                                ['account_id' => $invAccount->id, 'debit' => $totalValuation, 'credit' => 0],
                                ['account_id' => $eqAccount->id, 'debit' => 0, 'credit' => $totalValuation],
                            ],
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Opening stock journal could not be created: ' . $e->getMessage());
                }
            }

            DB::commit();

            return redirect()->route('inventory.index')->with('success', "Successfully registered {$createdCount} opening stock item(s) (" . number_format($totalWeight, 2) . " kg) into yard inventory.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to save opening stock: ' . $e->getMessage());
        }
    }

    /**
     * Show edit page for opening stock coil.
     */
    public function editOpeningStock($id)
    {
        $coil = Coil::findOrFail($id);

        if ($coil->purchase_id !== null) {
            return redirect()->route('inventory.index')->with('error', 'This coil originated from a vendor purchase invoice and must be managed via Purchases.');
        }

        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $lots = Lot::where('status', 'active')->latest()->get();
        $vendors = Vendor::where('status', '1')->orderBy('name')->get();

        return view('frontend.pages.inventory.edit_opening_stock', compact('coil', 'warehouses', 'lots', 'vendors'));
    }

    /**
     * Update an opening stock coil.
     */
    public function updateOpeningStock(Request $request, $id)
    {
        $coil = Coil::findOrFail($id);

        if ($coil->purchase_id !== null) {
            return redirect()->back()->with('error', 'This coil originated from a vendor purchase invoice and must be managed via Purchases.');
        }

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'lot_id'       => 'nullable|exists:lots,id',
            'coil_number'  => 'required|string|max:100|unique:coils,coil_number,' . $id,
            'thickness'    => 'required|string|max:100',
            'width'        => 'required|string|max:100',
            'length'       => 'required|string|max:100',
            'piece_count'  => 'required|numeric|min:0.01',
            'net_weight'   => 'required|numeric|min:0.01',
            'rate_per_ton' => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string',
        ]);

        $prevNetWeight = (float) $coil->net_weight;
        $prevRemaining = (float) $coil->remaining_weight;
        $consumedWeight = max(0, $prevNetWeight - $prevRemaining);

        $newNetWeight = (float) $request->net_weight;
        if ($newNetWeight < $consumedWeight) {
            return redirect()->back()->withInput()->with('error', "Net weight cannot be less than already sold/consumed weight (" . number_format($consumedWeight, 2) . " kg).");
        }

        $newRemainingWeight = $newNetWeight - $consumedWeight;
        $rate = (float) ($request->rate_per_ton ?? 0);
        $itemTotal = $newNetWeight * $rate;

        $coil->update([
            'warehouse_id'     => $request->warehouse_id,
            'lot_id'           => $request->filled('lot_id') ? $request->lot_id : null,
            'coil_number'      => trim($request->coil_number),
            'thickness'        => $request->thickness,
            'width'            => $request->width,
            'length'           => $request->length,
            'piece_count'      => (float) $request->piece_count,
            'gross_weight'     => $newNetWeight,
            'net_weight'       => $newNetWeight,
            'remaining_weight' => $newRemainingWeight,
            'rate_per_ton'     => $rate,
            'total_price'      => $itemTotal,
            'status'           => ($newRemainingWeight <= 0) ? 'exhausted' : 'in_stock',
            'notes'            => $request->notes ?? $coil->notes,
            'updated_by'       => Auth::id(),
        ]);

        return redirect()->route('inventory.index')->with('success', "Opening stock coil {$coil->coil_number} updated successfully.");
    }

    /**
     * Delete an untouched opening stock coil.
     */
    public function destroyOpeningStock($id)
    {
        $coil = Coil::findOrFail($id);

        if ($coil->purchase_id !== null) {
            return redirect()->back()->with('error', 'This coil originated from a vendor purchase invoice and cannot be deleted from opening stock.');
        }

        if ((float)$coil->remaining_weight < (float)$coil->net_weight) {
            return redirect()->back()->with('error', "Coil {$coil->coil_number} has already been partially or fully sold/processed and cannot be deleted.");
        }

        $coilNumber = $coil->coil_number;
        $coil->delete();

        return redirect()->route('inventory.index')->with('success', "Opening stock coil {$coilNumber} has been removed from inventory.");
    }
    /**
     * Display a unified listing of steel coil inventory & stock registry.
     */
    public function index(Request $request)
    {
        $query = Coil::with(['lot.vendor', 'vendor', 'warehouse', 'purchase']);

        // Search filter (coil number, thickness, width, length, vendor name, lot number)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('coil_number', 'like', "%{$search}%")
                  ->orWhere('thickness', 'like', "%{$search}%")
                  ->orWhere('width', 'like', "%{$search}%")
                  ->orWhere('length', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('lot', function ($q) use ($search) {
                      $q->where('lot_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Lot
        if ($request->filled('lot_id')) {
            $query->where('lot_id', $request->lot_id);
        }

        // Filter by Warehouse / Yard
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Status filter: default to 'in_stock' unless explicitly requested
        $status = $request->input('status', 'in_stock');
        if ($status === 'in_stock') {
            $query->where('status', 'in_stock')->where('remaining_weight', '>', 0);
        } elseif ($status === 'processing' || $status === 'in_processing') {
            $query->where('status', 'processing');
        } elseif ($status === 'reserved') {
            $query->where('status', 'reserved');
        } elseif ($status === 'exhausted') {
            $query->where(function ($q) {
                $q->where('status', 'exhausted')->orWhere('remaining_weight', '<=', 0);
            });
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $coils = $query->latest()->paginate(25)->withQueryString();

        // Auxiliary data for filters
        $lots = Lot::where('status', 'active')->latest()->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $vendors = Vendor::where('status', '1')->orderBy('name')->get();

        // Top Summary KPI Metrics across the entire yard
        $totalInStockWeight = (float) Coil::where('status', 'in_stock')->sum('remaining_weight');
        $totalIntakeWeight  = (float) Coil::where('status', 'in_stock')->sum('net_weight');
        $inStockCount       = Coil::where('status', 'in_stock')->where('remaining_weight', '>', 0)->count();
        $totalCoilsCount    = Coil::count();
        $totalValuation     = (float) Coil::where('status', 'in_stock')->where('remaining_weight', '>', 0)
            ->get()
            ->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);

        // Thickness-wise Weighted Average Cost & Stock Breakdown
        $breakdownQuery = Coil::whereIn('status', ['in_stock', 'processing'])
            ->where('remaining_weight', '>', 0);

        if ($request->filled('warehouse_id')) {
            $breakdownQuery->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('lot_id')) {
            $breakdownQuery->where('lot_id', $request->lot_id);
        }

        $thicknessBreakdown = $breakdownQuery->get()->groupBy(function ($coil) {
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

        return view('frontend.pages.inventory.index', compact(
            'coils',
            'lots',
            'warehouses',
            'vendors',
            'totalInStockWeight',
            'totalIntakeWeight',
            'inStockCount',
            'totalCoilsCount',
            'totalValuation',
            'thicknessBreakdown'
        ));
    }

    /**
     * Update coil status (e.g. in_stock, processing, reserved, exhausted).
     */
    public function updateStatus(Request $request, $id)
    {
        $status = $request->status === 'in_processing' ? 'processing' : $request->status;
        $request->merge(['status' => $status]);

        $request->validate([
            'status' => 'required|in:in_stock,reserved,processing,exhausted,scrapped',
        ]);

        $coil = Coil::findOrFail($id);
        $coil->status = $status;
        $coil->save();

        return redirect()->back()->with('success', "Coil {$coil->coil_number} status updated to " . ucfirst(str_replace('_', ' ', $coil->status)));
    }

    /**
     * Export mPDF stock report.
     */
    public function downloadPdf(Request $request)
    {
        ini_set('memory_limit', '512M');

        $query = Coil::where('status', 'in_stock')
            ->where('remaining_weight', '>', 0)
            ->with(['lot.vendor', 'warehouse'])
            ->latest();

        if ($request->filled('lot_id')) {
            $query->where('lot_id', $request->lot_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
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

        $coils = $query->get();

        $html = view('pdf.inventory', compact('coils'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
        ]);
        $mpdf->WriteHTML($html);
        $filename = 'Steel_Inventory_Stock_Report_' . now()->format('Y_m_d_His') . '.pdf';
        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
