<?php
namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\DailyExpense;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\PdfService;

class RevenueController extends Controller
{
    public function index()
    {
        $revenues = Revenue::orderByDesc('year')->orderByDesc('month')->get();
        return view('frontend.pages.report.revenue.index', compact('revenues'));
    }

    public function downloadPdf()
    {
        $revenues = Revenue::orderByDesc('year')->orderByDesc('month')->get();
        $filename = 'Monthly_Revenue_Report_' . now()->format('Y_m_d') . '.pdf';

        return PdfService::viewToResponse('pdf.revenue', compact('revenues'), $filename);
    }

    public function generate()
    {
        $month = now()->month;
        $year = now()->year;
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $grossSales = (float) Sale::where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('order_date', [$startDate, $endDate])
              ->orWhere(function($sub) use ($startDate, $endDate) {
                  $sub->whereNull('order_date')
                      ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
              });
        })->whereNull('deleted_at')->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(payble, total)'));

        $totalReturns = (float) \App\Models\ProductReturn::whereBetween('return_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->sum('total_refund_amount');

        $totalSales = max(0, $grossSales - $totalReturns);

        $totalPurchases = (float) Purchase::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->sum('total_price');

        $totalExpenses = (float) DailyExpense::where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate])
              ->orWhere(function($sub) use ($startDate, $endDate) {
                  $sub->whereNull('date')
                      ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
              });
        })->sum('amount');

        Revenue::updateOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'total_sales' => $totalSales,
                'total_purchases' => $totalPurchases,
                'total_expenses' => $totalExpenses,
            ]
        );

        return redirect()->route('revenues.index')
            ->with('success', 'Monthly revenue summary updated successfully!');
    }

     public function export($id)
    {
        $revenue = Revenue::findOrFail($id);
        $filename = "Revenue_Report_{$revenue->month_name}_{$revenue->year}.pdf";

        return PdfService::viewToResponse('frontend.pages.report.revenue.pdf', compact('revenue'), $filename);
    }
}