<x-filament-widgets::widget>
    <div @class(['ks-posta-wrap', 'ks-posta-wrap--new' => $neprectene > 0])>
        <x-filament::section>
            <x-slot name="heading">
                <span class="ks-posta-head">
                    <span class="ks-posta-ico">📨</span>
                    @if ($neprectene > 0)
                        <span class="ks-posta-count">{{ $neprectene }}</span>
                        <span class="ks-posta-title">{{ $neprectene === 1 ? 'nový e-mail' : ($neprectene < 5 ? 'nové e-maily' : 'nových e-mailů') }}</span>
                    @else
                        <span class="ks-posta-title ks-posta-title--calm">Pošta — vše přečteno</span>
                    @endif
                </span>
            </x-slot>

            <x-slot name="headerEnd">
                <a href="{{ $vseUrl }}" class="ks-posta-all">Celá pošta →</a>
            </x-slot>

            @if (empty($zpravy))
                <p class="ks-posta-empty">Zatím žádné e-maily. Pošta na <strong>servis&#64;konzolak.com</strong> se objeví tady.</p>
            @else
                <div class="ks-posta">
                    @foreach ($zpravy as $z)
                        <a href="{{ $z['url'] }}" @class(['ks-posta-row', 'ks-posta-row--new' => ! $z['precteno']])>
                            <span class="ks-posta-dot">@if (! $z['precteno'])<span class="ks-dot"></span>@endif</span>
                            <span class="ks-posta-od">{{ $z['od'] }}</span>
                            <span class="ks-posta-mid">
                                <span class="ks-posta-predmet">{{ $z['predmet'] }}</span>
                                <span class="ks-posta-nahled">{{ $z['nahled'] }}</span>
                            </span>
                            <span class="ks-posta-meta">
                                @if ($z['zakazka'])<span class="ks-posta-zak">{{ $z['zakazka'] }}</span>@endif
                                <span class="ks-posta-datum">{{ $z['datum']?->format('d.m. H:i') }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>

    <style>
        .ks-posta-wrap--new .fi-section {
            border: 2px solid #C8992E !important;
            box-shadow: 0 0 0 4px rgba(200,153,46,.15) !important;
        }
        .ks-posta-head { display:flex; align-items:center; gap:.5rem; }
        .ks-posta-ico { font-size:1.15rem; }
        .ks-posta-count {
            display:inline-flex; align-items:center; justify-content:center;
            min-width:1.6rem; height:1.6rem; padding:0 .45rem;
            background:#dc2626; color:#fff; font-weight:800; font-size:.95rem;
            border-radius:999px;
        }
        .ks-posta-title { font-weight:700; font-size:1rem; }
        .ks-posta-title--calm { font-weight:600; color:#6b7280; }
        .ks-posta-all { font-size:.8rem; color:#6b7280; text-decoration:none; white-space:nowrap; }
        .ks-posta-all:hover { color:#C8992E; }
        .ks-posta-empty { font-size:.9rem; color:#6b7280; }

        .ks-posta { display:flex; flex-direction:column; }
        .ks-posta-row {
            display:grid; grid-template-columns:1rem 10rem 1fr auto; gap:.7rem; align-items:center;
            padding:.55rem .4rem; border-bottom:1px solid #eceef1;
            text-decoration:none; color:#1f2937; font-size:.9rem;
        }
        :is(.dark) .ks-posta-row { color:#e5e7eb; border-color:#2b3440; }
        .ks-posta-row:last-child { border-bottom:0; }
        .ks-posta-row:hover { background:rgba(200,153,46,.07); }
        .ks-posta-row--new { background:rgba(220,38,38,.05); font-weight:600; }
        .ks-posta-row--new:hover { background:rgba(220,38,38,.09); }

        .ks-posta-dot { display:flex; justify-content:center; }
        .ks-dot { width:.55rem; height:.55rem; border-radius:50%; background:#dc2626; display:block; }
        .ks-posta-od { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ks-posta-mid { overflow:hidden; }
        .ks-posta-predmet { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ks-posta-nahled { display:block; font-weight:400; font-size:.78rem; color:#9ca3af;
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ks-posta-meta { text-align:right; white-space:nowrap; font-weight:400; }
        .ks-posta-zak { display:inline-block; font-size:.7rem; background:rgba(15,32,56,.08); color:#0F2038;
            padding:.05rem .35rem; border-radius:.25rem; margin-right:.4rem; }
        :is(.dark) .ks-posta-zak { background:rgba(255,255,255,.1); color:#e5e7eb; }
        .ks-posta-datum { font-size:.78rem; color:#9ca3af; }

        @media (max-width:820px) {
            .ks-posta-row { grid-template-columns:1rem 1fr auto; }
            .ks-posta-od { grid-column:2 / -1; font-size:.82rem; color:#6b7280; }
        }
    </style>
</x-filament-widgets::widget>
