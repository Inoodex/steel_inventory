<?php

namespace App\Http\Controllers;

use App\Models\Coil;
use App\Models\Lot;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
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

        return view('frontend.pages.inventory.index', compact(
            'coils',
            'lots',
            'warehouses',
            'vendors',
            'totalInStockWeight',
            'totalIntakeWeight',
            'inStockCount',
            'totalCoilsCount',
            'totalValuation'
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
