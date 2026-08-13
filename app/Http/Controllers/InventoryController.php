<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Coil;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = collect();
        $inventories = Inventory::latest()->get();
        $coils = Coil::where('status', 'in_stock')->latest()->get();
        return view('frontend.pages.inventory.index', compact('products', 'inventories', 'coils'));
    }

    public function downloadPdf()
    {
        ini_set('memory_limit', '512M');
        $inventories = Inventory::latest()->get();
        $coils = Coil::where('status', 'in_stock')->latest()->get();
        $html = view('pdf.inventory', compact('inventories', 'coils'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
        ]);
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('Inventory_Stock_Report_' . now()->format('Y_m_d_His') . '.pdf', 'I'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'opening_stock' => 'required|numeric|min:0',
        ]);

        $inventory = new Inventory();
        $inventory->opening_stock = $request->opening_stock;
        $inventory->current_stock = $request->opening_stock;
        $inventory->notes = $request->notes;
        $inventory->save();

        return redirect()->back()->with('success', 'Inventory item created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inventory $inventory)
    {
        $request->validate([
            'current_stock' => 'required|numeric|min:0',
        ]);

        $inventory->current_stock = $request->current_stock;
        $inventory->notes = $request->notes;
        $inventory->update();

        return redirect()->back()->with('success', 'Inventory updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory)
    {
        $inventory->delete();
        return redirect()->back()->with('success', 'Inventory deleted successfully.');
    }
}
