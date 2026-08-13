<?php

namespace App\Http\Controllers;

use App\Models\Coil;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display a listing of active steel coil inventory.
     */
    public function index()
    {
        $coils = Coil::where('status', 'in_stock')
            ->where('remaining_weight', '>', 0)
            ->with(['lot.vendor', 'warehouse'])
            ->latest()
            ->get();

        return view('frontend.pages.inventory.index', compact('coils'));
    }

    public function downloadPdf()
    {
        ini_set('memory_limit', '512M');
        $coils = Coil::where('status', 'in_stock')
            ->where('remaining_weight', '>', 0)
            ->with(['lot.vendor', 'warehouse'])
            ->latest()
            ->get();

        $html = view('pdf.inventory', compact('coils'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
        ]);
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('Steel_Inventory_Stock_Report_' . now()->format('Y_m_d_His') . '.pdf', 'I'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
