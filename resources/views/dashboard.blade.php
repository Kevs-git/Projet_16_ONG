<x-app-layout>
    <div style="display:flex; min-height:100vh;">

        <div style="width:250px; background:#1e293b; color:white; padding:20px;">
            <h2 style="margin-bottom:30px;">ONG Dashboard</h2>
            <ul style="list-style:none; padding:0;">
                <li style="margin-bottom:20px;">Dashboard</li>
                <li style="margin-bottom:20px;">Campagnes</li>
                <li style="margin-bottom:20px;">Dons</li>
                <li style="margin-bottom:20px;">Donateurs</li>
                <li style="margin-bottom:20px;">Catégories</li>
            </ul>
        </div>

        <div style="flex:1; padding:30px;">
            <h1>Bienvenue dans le Dashboard ONG</h1>
            <hr>

            <div style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:20px; margin-top:30px;">
                <div style="padding:20px; background:#f8fafc; border-radius:12px;">
                    <h3>Total campagnes</h3>
                    <p style="font-size:2rem; margin:0;">{{ $campaignCount }}</p>
                </div>
                <div style="padding:20px; background:#f8fafc; border-radius:12px;">
                    <h3>Total dons</h3>
                    <p style="font-size:2rem; margin:0;">{{ $donationCount }}</p>
                </div>
                <div style="padding:20px; background:#f8fafc; border-radius:12px;">
                    <h3>Total donateurs</h3>
                    <p style="font-size:2rem; margin:0;">{{ $donorCount }}</p>
                </div>
            </div>

            <div style="margin-top:30px; display:grid; grid-template-columns:2fr 1fr; gap:20px;">
                <div style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                    <h3>Campagnes récentes</h3>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom:1px solid #e2e8f0;">
                                <th style="padding:10px 0;">Titre</th>
                                <th style="padding:10px 0;">Collecté</th>
                                <th style="padding:10px 0;">Urgente</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaigns as $campaign)
                                <tr style="border-bottom:1px solid #e2e8f0;">
                                    <td style="padding:12px 0;">{{ $campaign->title }}</td>
                                    <td style="padding:12px 0;">{{ number_format($campaign->collected_amount, 2, ',', ' ') }} €</td>
                                    <td style="padding:12px 0;">
                                        @if($campaign->is_urgent)
                                            <span style="background:#f43f5e; color:white; padding:4px 8px; border-radius:999px; font-size:0.85rem;">Urgente</span>
                                        @else
                                            <span style="background:#e2e8f0; color:#475569; padding:4px 8px; border-radius:999px; font-size:0.85rem;">Normale</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                    <h3>Top donateurs</h3>
                    <ol style="padding-left:20px; margin:0;">
                        @foreach($topDonors as $donor)
                            <li style="margin-bottom:12px;">
                                {{ $donor->name }} — {{ number_format($donor->donations_sum_amount, 2, ',', ' ') }} €
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <div style="margin-top:30px; background:#fff; padding:20px; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                <h3>Dons par période</h3>
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid #e2e8f0;">
                            <th style="padding:10px 0;">Date</th>
                            <th style="padding:10px 0;">Montant total</th>
                        </tr>
                    </thead>
                    <tbody>
                            @foreach($donationsByPeriod as $entry)
                                <tr style="border-bottom:1px solid #e2e8f0;">
                                    <td style="padding:12px 0;">{{ $entry->date }}</td>
                                <td style="padding:12px 0;">{{ number_format($entry->total, 2, ',', ' ') }} €</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>