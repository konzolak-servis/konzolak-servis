<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <span class="ks-ukoly__title">
                Co řešit
                @if ($celkem > 0)
                    <span class="ks-ukoly__count">{{ $celkem }}</span>
                @endif
            </span>
        </x-slot>

        @if (empty($skupiny))
            <p class="ks-ukoly__done">Hotovo – nic nečeká na řešení. 🎉</p>
        @else
            <div class="ks-ukoly">
                @foreach ($skupiny as $s)
                    <section class="ks-grp ks-grp--{{ $s['barva'] }}">
                        <header class="ks-grp__head">
                            <span class="ks-grp__name">{{ $s['nadpis'] }}</span>
                            <span class="ks-grp__n">{{ $s['pocet'] }}</span>
                        </header>

                        @foreach ($s['polozky'] as $p)
                            <a href="{{ $p['url'] }}" @class(['ks-row', 'ks-row--urgent' => $p['urgent'] ?? false])>
                                <span class="ks-row__cislo">{{ $p['cislo'] }}</span>
                                <span class="ks-row__popis">
                                    {{ $p['popis'] }}
                                    @if (! empty($p['tag']))
                                        <span class="ks-row__tag">{{ $p['tag'] }}</span>
                                    @endif
                                </span>
                                <span class="ks-row__meta">
                                    @if (! empty($p['meta'])){{ $p['meta'] }}@endif
                                    @if (! empty($p['castka']))
                                        <span class="ks-row__castka">{{ number_format((float) $p['castka'], 0, ',', ' ') }} Kč</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach

                        @if (($s['skryto'] ?? 0) > 0 && $s['vse_url'])
                            <a href="{{ $s['vse_url'] }}" class="ks-grp__vse">+ {{ $s['skryto'] }} další →</a>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    <style>
        .ks-ukoly__title{ display:inline-flex; align-items:center; gap:.5rem; }
        .ks-ukoly__count{ background:#C8992E; color:#fff; font-size:.75rem; font-weight:700;
            min-width:1.4rem; height:1.4rem; padding:0 .35rem; border-radius:.7rem;
            display:inline-flex; align-items:center; justify-content:center; }
        .ks-ukoly__done{ font-size:.95rem; color:#059669; font-weight:600; padding:.25rem; }

        .ks-ukoly{ --line:#eceef1; --txt:#374151; --dim:#6b7280; --hover:#faf6ec;
            display:grid; grid-template-columns:repeat(auto-fit,minmax(340px,1fr)); gap:1.25rem 1.75rem; }
        :is(.dark) .ks-ukoly{ --line:#374151; --txt:#d1d5db; --dim:#9ca3af; --hover:#2a2417; }

        .ks-grp{ border-left:3px solid var(--ac,#9ca3af); padding-left:.7rem; }
        .ks-grp--danger{ --ac:#dc2626; }
        .ks-grp--warning{ --ac:#d97706; }
        .ks-grp--success{ --ac:#059669; }
        .ks-grp--info{ --ac:#0ea5e9; }
        .ks-grp--gray{ --ac:#9ca3af; }

        .ks-grp__head{ display:flex; align-items:center; gap:.5rem; margin-bottom:.35rem; }
        .ks-grp__name{ font-weight:700; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:var(--ac); }
        .ks-grp__n{ font-size:.72rem; font-weight:700; color:var(--dim);
            background:color-mix(in srgb, var(--ac) 14%, transparent); border-radius:.6rem; padding:0 .45rem; }

        .ks-row{ display:grid; grid-template-columns:auto 1fr auto; gap:.15rem .7rem; align-items:baseline;
            padding:.45rem .35rem; border-bottom:1px solid var(--line); text-decoration:none; color:var(--txt); font-size:.88rem; }
        .ks-row:hover{ background:var(--hover); }
        .ks-row--urgent .ks-row__meta{ color:#dc2626; font-weight:700; }
        .ks-row__cislo{ font-weight:700; color:#C8992E; white-space:nowrap; }
        .ks-row__popis{ min-width:0; }
        .ks-row__tag{ display:inline-block; margin-left:.4rem; font-size:.7rem; padding:.05rem .4rem;
            border-radius:.35rem; background:color-mix(in srgb, var(--ac) 15%, transparent); color:var(--ac); white-space:nowrap; }
        .ks-row__meta{ text-align:right; color:var(--dim); white-space:nowrap; font-size:.8rem; }
        .ks-row__castka{ display:block; color:var(--txt); font-weight:600; }
        .ks-grp__vse{ display:inline-block; margin-top:.4rem; font-size:.8rem; color:#C8992E; text-decoration:none; }
        .ks-grp__vse:hover{ text-decoration:underline; }

        @media (max-width:520px){
            .ks-row{ grid-template-columns:auto 1fr; }
            .ks-row__meta{ grid-column:2; text-align:left; }
        }
    </style>
</x-filament-widgets::widget>
