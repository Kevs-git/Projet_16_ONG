<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    public function show(Donation $donation)
    {
        $donation->load(['campaign', 'donor']);

        $pdf = Pdf::loadView('pdf.donation_receipt', ['donation' => $donation]);

        return $pdf->download('receipt-'.$donation->receipt_number.'.pdf');
    }
}
