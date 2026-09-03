<x-filament-widgets::widget>
    @if (! empty($polozky))
        <x-filament::section>
            <x-slot name="heading">
                <span style="color:#dc2626">⏰ Splatnosti a platnosti ({{ count($polozky) }})</span>
            </x-slot>

            <div class="ks-spl">
                @foreach ($polozky as $p)
                    <a href="{{ $p['url'] }}" class="ks-spl-row">
                        <span class="ks-spl-typ">{{ $p['typ'] }}</span>
                        <span class="ks-spl-nazev">{{ $p['nazev'] }}</span>
                        <span class="ks-spl-datum">{{ $p['datum']->format('d.m.Y') }}</span>
                        <span @class(['ks-spl-dni', 'ks-spl-dni--po' => $p['dni'] < 0, 'ks-spl-dni--brzy' => $p['dni'] >= 0 && $p['dni'] <= 7])>
                            @if ($p['dni'] < 0)
                                po termínu {{ abs($p['dni']) }} d
                            @elseif ($p['dni'] === 0)
                                dnes
                            @else
                                za {{ $p['dni'] }} d
                            @endif
                        </span>
                        <span class="ks-spl-castka">{{ $p['castka'] ? number_format($p['castka'], 0, ',', ' ') . ' Kč' : '' }}</span>
                    </a>
                @endforeach
            </div>
        </x-filament::section>

        <style>
            .ks-spl{ --line:#e5e7eb; --dim:#6b7280; --txt:#1f2937; display:flex; flex-direction:column; }
            :is(.dark) .ks-spl{ --line:#374151; --dim:#9ca3af; --txt:#f3f4f6; }
            .ks-spl-row{ display:grid; grid-template-columns:1.3fr 2fr .9fr .9fr .8fr; gap:.75rem; align-items:center;
                padding:.5rem .4rem; border-bottom:1px solid var(--line); font-size:.85rem; color:var(--txt); text-decoration:none; }
            .ks-spl-row:last-child{ border-bottom:0; }
            .ks-spl-row:hover{ background:rgba(220,38,38,.06); }
            .ks-spl-typ{ font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; color:var(--dim); }
            .ks-spl-nazev{ font-weight:600; }
            .ks-spl-datum{ color:var(--dim); }
            .ks-spl-dni{ font-weight:700; color:#059669; text-align:right; white-space:nowrap; }
            .ks-spl-dni--brzy{ color:#d97706; }
            .ks-spl-dni--po{ color:#dc2626; }
            .ks-spl-castka{ text-align:right; color:var(--dim); white-space:nowrap; }
            @media (max-width:820px){ .ks-spl-row{ grid-template-columns:1fr 1fr; } .ks-spl-typ,.ks-spl-castka{ display:none; } }
        </style>
    @endif
</x-filament-widgets::widget>
