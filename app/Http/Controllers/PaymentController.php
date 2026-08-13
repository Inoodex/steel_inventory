<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Customer;

class PaymentController extends Controller
{
    public function payments(Request $request, $id, $payment_for = 2)
    {
        $payments = Payment::where('sale_id', $id)->get();
        $bill = Sale::where('id', $id)->first();
        
        return view('frontend.pages.payment.bill_payment', compact('payments', 'id', 'payment_for', 'bill'));
    }

    public function addPayment(Request $request)
    {
        $bill = Sale::where('id', $request->id)->first();
        if (!$bill) {
            return redirect()->back()->with(['error' => 'Sale not found.']);
        }

        $payment = new Payment;
        $payment->payment_for = $request->payment_for ?? 2;
        $payment->customer_id = $bill->customer_id;
        $payment->sale_id = $bill->id;
        $payment->payment_method = $request->payment_method_id ?? 'cash';
        $payment->amount = $request->amount;
        $payment->remarks = $request->remarks;
        $payment->save();

        $bill->advanced_payment = ($bill->advanced_payment ?? 0) + $request->amount;
        $bill->due_payment = max(0, ($bill->payble ?? $bill->total) - $bill->advanced_payment);
        $bill->status = $bill->due_payment <= 0 ? 'paid' : 'partial';
        $bill->update();

        return redirect()->back()->with(['success' => 'Payment added successfully.']);
    }

    public function updatePayment(Request $request, $id)
    {
        $payment = Payment::where('id', $id)->first();
        if (!$payment) {
            return redirect()->back()->with(['error' => 'Payment not found.']);
        }

        $bill = Sale::where('id', $payment->sale_id)->first();
        if (!$bill) {
            return redirect()->back()->with(['error' => 'Sale not found.']);
        }

        $bill->advanced_payment = max(0, ($bill->advanced_payment ?? 0) - $payment->amount + $request->amount);
        $bill->due_payment = max(0, ($bill->payble ?? $bill->total) - $bill->advanced_payment);
        $bill->status = $bill->due_payment <= 0 ? 'paid' : 'partial';
        $bill->update();

        $payment->payment_method = $request->payment_method_id ?? $payment->payment_method;
        $payment->amount = $request->amount;
        $payment->remarks = $request->remarks;
        $payment->update();

        return redirect()->back()->with(['success' => 'Payment updated successfully.']);
    }

    public function deletePayment(Request $request, $id)
    {
        $payment = Payment::where('id', $id)->first();
        if (!$payment) {
            return redirect()->back()->with(['error' => 'Payment not found.']);
        }

        $bill = Sale::where('id', $payment->sale_id)->first();
        if ($bill) {
            $bill->advanced_payment = max(0, ($bill->advanced_payment ?? 0) - $payment->amount);
            $bill->due_payment = max(0, ($bill->payble ?? $bill->total) - $bill->advanced_payment);
            $bill->status = $bill->due_payment <= 0 ? 'paid' : ($bill->advanced_payment > 0 ? 'partial' : 'credit');
            $bill->update();
        }

        $payment->delete();

        return redirect()->back()->with(['success' => 'Payment deleted successfully.']);
    }
}
