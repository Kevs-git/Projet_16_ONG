<?php

namespace App\Exports;

use App\Models\Donation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinancialReportExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Donation::with(['user', 'campaign'])
            ->get()
            ->map(function($donation) {
                return [
                    $donation->id,
                    $donation->user->name,
                    $donation->campaign->title,
                    $donation->amount / 100 . ' EUR',
                    $donation->created_at->format('d/m/Y'),
                ];
            });
    }

    public function headings(): array {
        return ['ID', 'Donateur', 'Campagne', 'Montant', 'Date'];
    }
}