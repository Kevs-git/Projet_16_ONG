<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = Campaign::withCount(['donations as total_donations_amount' => function ($query) {
            $query->select(DB::raw('COALESCE(SUM(amount), 0)')); 
        }])->get();

        $donationsByPeriod = Donation::selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->take(30)
            ->get();

        $topDonors = Donor::withSum('donations', 'amount')
            ->orderByDesc('donations_sum_amount')
            ->take(10)
            ->get();

        return view('dashboard', [
            'campaigns' => $campaigns,
            'donationCount' => Donation::count(),
            'campaignCount' => Campaign::count(),
            'donorCount' => Donor::count(),
            'donationsByPeriod' => $donationsByPeriod,
            'topDonors' => $topDonors,
        ]);
    }
}
