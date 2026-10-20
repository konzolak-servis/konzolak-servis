<x-filament-panels::page>
    <div class="ks-cal">
        <div class="ks-cal__bar">
            <div class="ks-cal__title">{{ $this->nadpisMesice }}</div>
            <div class="ks-cal__nav">
                <x-filament::button size="sm" color="gray" wire:click="predchozi" icon="heroicon-m-chevron-left" />
                <x-filament::button size="sm" color="gray" wire:click="dnes">Dnes</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="dalsi" icon="heroicon-m-chevron-right" />
            </div>
        </div>

        <div class="ks-cal__legend">
            <span><i style="background:#f59e0b"></i> Přijato</span>
            <span><i style="background:#0ea5e9"></i> Diagnostika</span>
            <span><i style="background:#f97316"></i> Čeká na díl</span>
            <span><i style="background:#10b981"></i> Hotovo</span>
            <span><i style="background:#64748b"></i> Vydáno</span>
            <span><i style="background:#ef4444"></i> Nerentabilní</span>
        </div>

        <div class="ks-cal__grid ks-cal__head">
            @foreach (['Po','Út','St','Čt','Pá','So','Ne'] as $d)
                <div class="ks-cal__dow">{{ $d }}</div>
            @endforeach
        </div>

        @foreach ($this->tydny as $tyden)
            <div class="ks-cal__grid">
                @foreach ($tyden as $i => $den)
                    <div @class([
                        'ks-cal__day',
                        'ks-cal__day--pravy' => $i >= 4,
                        'ks-cal__day--out' => ! $den['v_mesici'],
                        'ks-cal__day--today' => $den['dnes'],
                    ])>
                        <div class="ks-cal__num">{{ $den['datum']->day }}</div>
                        <div class="ks-cal__events">
                            @foreach ($den['zakazky'] as $z)
                                <a href="{{ $z['url'] }}"
                                   class="ks-cal__ev ks-cal__ev--{{ $z['barva'] }}"
                                   aria-label="{{ $z['cislo'] }} · {{ $z['kdo'] }} · {{ $z['stav'] }}">
                                    <strong>@if ($z['hotovo'])<span class="ks-cal__ok">✓</span> @endif@if ($z['dil_objednany'])<span class="ks-cal__truck">🚚</span> @endif{{ $z['kdo'] ?: $z['cislo'] }}</strong>
                                    <span class="ks-cal__ev-sub">{{ $z['co'] }}</span>
                                    {{-- detail se ukáže po najetí kurzorem --}}
                                    <span class="ks-cal__pop">
                                        <b>{{ $z['cislo'] }} · {{ $z['stav'] }}@if ($z['dil_objednany']) · 🚚 díl objednán @endif</b>
                                        <em>{{ $z['kdo'] }}</em>
                                        @foreach ($z['zarizeni'] as $zar)<span>• {{ $zar }}</span>@endforeach
                                        @if ($z['zavada'])<span class="ks-cal__pop-zav">{{ \Illuminate\Support\Str::limit($z['zavada'], 160) }}</span>@endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <style>
        .ks-cal{ --ks-line:#e5e7eb; --ks-dim:#6b7280; --ks-cell:#ffffff; --ks-out:#f9fafb; --ks-title:#1f2937; --ks-today:#fffdf6; }
        :is(.dark) .ks-cal{ --ks-line:#374151; --ks-dim:#9ca3af; --ks-cell:#1f2937; --ks-out:#161e2e; --ks-title:#f3f4f6; --ks-today:#2a2417; }
        .ks-cal__bar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem; }
        .ks-cal__title{ font-size:1.35rem; font-weight:700; text-transform:capitalize; color:var(--ks-title); }
        .ks-cal__nav{ display:flex; gap:.4rem; }
        .ks-cal__legend{ display:flex; flex-wrap:wrap; gap:.9rem; font-size:.78rem; color:var(--ks-dim); margin-bottom:.6rem; }
        .ks-cal__legend i{ display:inline-block; width:.7rem; height:.7rem; border-radius:.2rem; margin-right:.3rem; vertical-align:middle; }
        .ks-cal__grid{ display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:.35rem; }
        .ks-cal__head{ margin-bottom:.35rem; }
        .ks-cal__dow{ font-size:.75rem; font-weight:700; text-transform:uppercase; color:var(--ks-dim); text-align:center; padding:.2rem 0; }
        .ks-cal__day{
            min-height:5.2rem; min-width:0; border:1px solid var(--ks-line); border-radius:.6rem;
            padding:.35rem; display:flex; flex-direction:column; gap:.25rem; background:var(--ks-cell);
        }
        .ks-cal__day--out{ background:var(--ks-out); opacity:.75; }
        .ks-cal__day--today{ border-color:#C8992E; box-shadow:inset 0 0 0 1px #C8992E; background:var(--ks-today); }
        .ks-cal__num{ font-size:.8rem; font-weight:700; color:var(--ks-dim); }
        .ks-cal__day--today .ks-cal__num{ color:#C8992E; }
        .ks-cal__events{ display:flex; flex-direction:column; gap:.2rem; min-width:0; }
        .ks-cal__ev{
            position:relative; display:block; min-width:0; padding:.15rem .35rem; border-radius:.35rem; font-size:.72rem; line-height:1.15;
            color:#fff; text-decoration:none; border-left:3px solid rgba(0,0,0,.2);
        }
        .ks-cal__ev strong{ display:block; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ks-cal__ev-sub{ display:block; opacity:.85; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ks-cal__ok{ font-weight:700; }
        .ks-cal__ev:hover{ z-index:40; filter:brightness(1.08); }
        .ks-cal__pop{
            display:none; position:absolute; left:0; top:calc(100% + .25rem); z-index:50; width:max-content; max-width:min(22rem,80vw);
            padding:.55rem .7rem; border-radius:.55rem; background:#0b1a30; color:#f3f4f6; font-size:.78rem; line-height:1.35;
            border:1px solid rgba(236,205,130,.45); box-shadow:0 12px 32px rgba(0,0,0,.5); white-space:normal;
        }
        .ks-cal__ev:hover .ks-cal__pop, .ks-cal__ev:focus .ks-cal__pop{ display:block; }
        .ks-cal__day--pravy .ks-cal__pop{ left:auto; right:0; }
        .ks-cal__pop b{ display:block; color:#E8C77C; margin-bottom:.1rem; }
        .ks-cal__pop em{ display:block; font-style:normal; font-weight:600; margin-bottom:.15rem; }
        .ks-cal__pop span{ display:block; opacity:.92; }
        .ks-cal__pop .ks-cal__pop-zav{ margin-top:.3rem; padding-top:.3rem; border-top:1px solid rgba(255,255,255,.15); opacity:.75; }
        .ks-cal__ev--amber{ background:#f59e0b; }
        .ks-cal__ev--sky{ background:#0ea5e9; }
        .ks-cal__ev--orange{ background:#f97316; }
        .ks-cal__ev--emerald{ background:#10b981; }
        .ks-cal__ev--slate{ background:#64748b; }
        .ks-cal__ev--red{ background:#ef4444; }
        .ks-cal__ev--zinc{ background:#71717a; }
        .ks-cal__ev--gray{ background:#6b7280; }
        @media (max-width:820px){
            .ks-cal__day{ min-height:4.5rem; }
            .ks-cal__ev-sub{ display:none; }
        }
    </style>
</x-filament-panels::page>
