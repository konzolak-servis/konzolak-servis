@php
    $f = fn ($n) => number_format((float) $n, 0, ',', ' ') . ' Kč';
    $ziskCls = $zisk < 0 ? 'nb-neg' : 'nb-pos';
@endphp
<div class="nb-souhrn">
    <div class="nb-cards">
        <div class="nb-card"><span class="nb-l">Nákup (můj náklad)</span><span class="nb-v">{{ $f($naklad) }}</span></div>
        <div class="nb-card"><span class="nb-l">Prodej (nabídka)</span><span class="nb-v">{{ $f($prodej) }}</span></div>
        <div class="nb-card {{ $ziskCls }}">
            <span class="nb-l">Zisk</span>
            <span class="nb-v">{{ $f($zisk) }} <small>({{ $marze }} %)</small></span>
        </div>
    </div>
    <div class="nb-bar" title="Podíl nákladu a zisku na prodejní ceně">
        <div class="nb-bar-cost" style="width: {{ max(0, min(100, $nakladPct)) }}%"></div>
        <div class="nb-bar-profit" style="width: {{ max(0, min(100, 100 - $nakladPct)) }}%"></div>
    </div>
    <div class="nb-legend"><span><i class="nb-i-cost"></i> náklad</span><span><i class="nb-i-profit"></i> zisk</span></div>

    <style>
        .nb-souhrn{ --line:#e5e7eb; --dim:#6b7280; --cell:#fff; --txt:#1f2937; }
        :is(.dark) .nb-souhrn{ --line:#374151; --dim:#9ca3af; --cell:#1f2937; --txt:#f3f4f6; }
        .nb-cards{ display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; }
        .nb-card{ border:1px solid var(--line); border-radius:.7rem; padding:.7rem .9rem; background:var(--cell);
            display:flex; flex-direction:column; gap:.15rem; border-top:3px solid #94a3b8; }
        .nb-card.nb-pos{ border-top-color:#059669; }
        .nb-card.nb-neg{ border-top-color:#dc2626; }
        .nb-l{ font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; color:var(--dim); }
        .nb-v{ font-size:1.15rem; font-weight:800; color:var(--txt); }
        .nb-v small{ font-weight:600; color:var(--dim); }
        .nb-bar{ display:flex; height:1.1rem; border-radius:.55rem; overflow:hidden; margin-top:.9rem; background:var(--line); }
        .nb-bar-cost{ background:#94a3b8; }
        .nb-bar-profit{ background:#059669; }
        .nb-legend{ display:flex; gap:1rem; font-size:.75rem; color:var(--dim); margin-top:.35rem; }
        .nb-legend i{ display:inline-block; width:.7rem; height:.7rem; border-radius:.2rem; vertical-align:middle; margin-right:.25rem; }
        .nb-i-cost{ background:#94a3b8; } .nb-i-profit{ background:#059669; }
    </style>
</div>
