<div style="border:1px solid #cbd5e1; padding:20px; border-radius:12px; max-width:500px;">
    <h2 style="margin-bottom:16px;">Progression de la campagne</h2>

    @if($campaign)
        <p><strong>{{ $campaign->title }}</strong></p>
        <div style="background:#e2e8f0; border-radius:9999px; overflow:hidden; height:24px; margin-bottom:8px;">
            <div style="width:{{ $campaign->progress_percentage }}%; background:#38bdf8; height:100%;"></div>
        </div>
        <p>{{ $campaign->progress_percentage }}% collecté</p>
        <p>{{ $campaign->unique_donor_count }} donateur(s) unique(s)</p>
    @else
        <p>Campagne non trouvée.</p>
    @endif
</div>
