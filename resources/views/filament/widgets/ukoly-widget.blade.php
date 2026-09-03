<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Co je potřeba</x-slot>

        <div class="ks-ukoly">
            <div>
                <h3 class="ks-ukoly__h ks-ukoly__h--ok">Hotové k vydání ({{ $kVydani->count() }})</h3>
                @forelse ($kVydani as $z)
                    <a href="{{ $z['url'] }}" class="ks-ukoly__row">
                        <span class="ks-ukoly__cislo">{{ $z['cislo'] }}</span>
                        <span>{{ $z['kdo'] }} — {{ $z['co'] }}</span>
                    </a>
                @empty
                    <p class="ks-ukoly__empty">Nic nečeká na vydání.</p>
                @endforelse
            </div>

            <div>
                <h3 class="ks-ukoly__h ks-ukoly__h--wait">Čeká na díl ({{ $cekaNaDil->count() }})</h3>
                @forelse ($cekaNaDil as $z)
                    <a href="{{ $z['url'] }}" class="ks-ukoly__row">
                        <span class="ks-ukoly__cislo">{{ $z['cislo'] }}</span>
                        <span>
                            {{ $z['kdo'] }} — {{ $z['co'] }}
                            @if ($z['dil_objednany'])
                                <span class="ks-tag ks-tag--ok">díl objednán</span>
                            @else
                                <span class="ks-tag ks-tag--no">díl NEobjednán</span>
                            @endif
                            @if ($z['dil_info'])
                                <span class="ks-ukoly__note">{{ $z['dil_info'] }}</span>
                            @endif
                        </span>
                    </a>
                @empty
                    <p class="ks-ukoly__empty">Žádná zakázka nečeká na díl.</p>
                @endforelse
            </div>
        </div>
    </x-filament::section>

    <style>
        .ks-ukoly{ --row:#374151; --line:#eceef1; --hover:#faf6ec; --dim:#6b7280;
            display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:1.5rem; }
        :is(.dark) .ks-ukoly{ --row:#d1d5db; --line:#374151; --hover:#2a2417; --dim:#9ca3af; }
        .ks-ukoly__h{ font-weight:700; font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.5rem; }
        .ks-ukoly__h--ok{ color:#059669; }
        .ks-ukoly__h--wait{ color:#d97706; }
        .ks-ukoly__row{
            display:flex; gap:.6rem; align-items:baseline; padding:.5rem .35rem; border-radius:.4rem;
            border-bottom:1px solid var(--line); font-size:.9rem; color:var(--row); text-decoration:none;
        }
        .ks-ukoly__row:hover{ background:var(--hover); }
        .ks-ukoly__cislo{ font-weight:700; color:#C8992E; flex:0 0 auto; }
        .ks-ukoly__note{ display:block; font-size:.8rem; color:var(--dim); }
        .ks-ukoly__empty{ font-size:.9rem; color:var(--dim); padding:.5rem .25rem; }
        .ks-tag{ font-size:.7rem; padding:.05rem .4rem; border-radius:.35rem; white-space:nowrap; margin-left:.3rem; }
        .ks-tag--ok{ background:rgba(5,150,105,.15); color:#059669; }
        .ks-tag--no{ background:rgba(220,38,38,.15); color:#dc2626; }
    </style>
</x-filament-widgets::widget>
