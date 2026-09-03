<x-filament-panels::page>
    <div class="ks-fin">
        {{-- přepínač období --}}
        <div class="ks-fin__bar">
            <div class="ks-fin__seg">
                @foreach (['den' => 'Den', 'mesic' => 'Měsíc', 'rok' => 'Rok'] as $k => $v)
                    <button type="button" wire:click="$set('obdobi', '{{ $k }}')"
                        @class(['ks-seg', 'ks-seg--on' => $obdobi === $k])>{{ $v }}</button>
                @endforeach
            </div>

            <div class="ks-fin__pick">
                @if ($obdobi === 'den')
                    <input type="date" wire:model.live="den" class="ks-input">
                @elseif ($obdobi === 'rok')
                    <input type="number" wire:model.live="rok" min="2020" max="2100" class="ks-input" style="width:6rem">
                @else
                    <input type="month" wire:model.live="mesic" class="ks-input">
                @endif
            </div>
        </div>

        {{-- karty --}}
        @php($s = $this->staty)
        <div class="ks-cards">
            <div class="ks-card ks-card--in">
                <div class="ks-card__l">Příjmy · {{ $this->obdobiLabel }}</div>
                <div class="ks-card__v">{{ number_format($s['prijmy'], 0, ',', ' ') }} Kč</div>
                <div class="ks-card__s">{{ $s['pocet_oprav'] }} vydaných oprav</div>
            </div>
            <div class="ks-card ks-card--out">
                <div class="ks-card__l">Náklady · {{ $this->obdobiLabel }}</div>
                <div class="ks-card__v">{{ number_format($s['vydaje'], 0, ',', ' ') }} Kč</div>
                <div class="ks-card__s">nákup materiálu a vybavení</div>
            </div>
            <div class="ks-card ks-card--net">
                <div class="ks-card__l">Čistý příjem · {{ $this->obdobiLabel }}</div>
                <div class="ks-card__v">{{ number_format($s['cisty'], 0, ',', ' ') }} Kč</div>
                <div class="ks-card__s">příjmy − náklady</div>
            </div>
            <a href="{{ $this->skladUrl }}" class="ks-card ks-card--stock ks-card--link">
                <div class="ks-card__l">Hodnota skladu (teď)</div>
                <div class="ks-card__v">{{ number_format($s['hodnota_skladu'], 0, ',', ' ') }} Kč</div>
                <div class="ks-card__s">množství × prům. nákupní cena · otevřít sklad →</div>
            </a>
        </div>

        {{-- měsíční rozpad – klik na měsíc otevře jeho detail --}}
        @php($m = $this->mesice)
        <x-filament::section>
            <x-slot name="heading">Měsíční rozpad {{ $m['rok'] }}</x-slot>
            <x-slot name="description">Klikni na měsíc pro podrobný rozpis pohybů.</x-slot>
            <div style="overflow-x:auto">
                <table class="ks-tab ks-tab--click">
                    <thead>
                        <tr><th>Měsíc</th><th class="r">Pohybů</th><th class="r">Příjmy</th><th class="r">Náklady</th><th class="r">Čistý příjem</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($m['radky'] as $r)
                            <tr wire:click="otevriMesic({{ $r['cislo'] }})" @class(['ks-now' => $r['vybrany']])>
                                <td>{{ $r['nazev'] }} <span class="ks-arrow">→</span></td>
                                <td class="r">{{ $r['pocet'] ?: '—' }}</td>
                                <td class="r">{{ number_format($r['prijmy'], 0, ',', ' ') }}</td>
                                <td class="r">{{ number_format($r['vydaje'], 0, ',', ' ') }}</td>
                                <td class="r" @style(['color:#dc2626' => $r['cisty'] < 0])>
                                    {{ number_format($r['cisty'], 0, ',', ' ') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Celkem {{ $m['rok'] }}</td>
                            <td class="r">{{ collect($m['radky'])->sum('pocet') }}</td>
                            <td class="r">{{ number_format(collect($m['radky'])->sum('prijmy'), 0, ',', ' ') }}</td>
                            <td class="r">{{ number_format(collect($m['radky'])->sum('vydaje'), 0, ',', ' ') }}</td>
                            <td class="r">{{ number_format(collect($m['radky'])->sum('cisty'), 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>

        {{-- podrobný rozpis pohybů ve zvoleném období --}}
        <x-filament::section>
            <x-slot name="heading">Podrobný rozpis · {{ $this->obdobiLabel }}</x-slot>
            @php($pohyby = $this->pohyby)
            @if (empty($pohyby))
                <p class="ks-empty">V tomto období není žádný pohyb.</p>
            @else
                <div style="overflow-x:auto">
                    <table class="ks-tab">
                        <thead>
                            <tr>
                                <th>Datum</th><th>Popis</th><th>Kategorie</th><th>Doklad</th>
                                <th class="r">Příjem</th><th class="r">Výdej</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pohyby as $p)
                                <tr>
                                    <td>{{ $p['datum'] }}</td>
                                    <td>{{ $p['popis'] }}</td>
                                    <td><span class="ks-badge">{{ $p['kategorie'] ?: '—' }}</span></td>
                                    <td>
                                        @if ($p['doklad'][1])
                                            <a href="{{ $p['doklad'][1] }}" class="ks-doklad">{{ $p['doklad'][0] }}</a>
                                        @else
                                            <span class="ks-empty">{{ $p['doklad'][0] }}</span>
                                        @endif
                                    </td>
                                    <td class="r" style="color:#059669">
                                        {{ $p['typ'] === 'prijem' ? number_format($p['castka'], 0, ',', ' ') . ' Kč' : '' }}
                                    </td>
                                    <td class="r" style="color:#dc2626">
                                        {{ $p['typ'] === 'vydej' ? number_format($p['castka'], 0, ',', ' ') . ' Kč' : '' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">Celkem · {{ $this->obdobiLabel }}</td>
                                <td class="r">{{ number_format($s['prijmy'], 0, ',', ' ') }} Kč</td>
                                <td class="r">{{ number_format($s['vydaje'], 0, ',', ' ') }} Kč</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>

    <style>
        .ks-fin{ --line:#e5e7eb; --dim:#6b7280; --cell:#fff; --txt:#1f2937; display:flex; flex-direction:column; gap:1.25rem; }
        :is(.dark) .ks-fin{ --line:#374151; --dim:#9ca3af; --cell:#1f2937; --txt:#f3f4f6; }

        .ks-fin__bar{ display:flex; flex-wrap:wrap; gap:1rem; align-items:center; justify-content:space-between; }
        .ks-fin__seg{ display:inline-flex; border:1px solid var(--line); border-radius:.6rem; overflow:hidden; }
        .ks-seg{ padding:.4rem 1rem; font-size:.85rem; font-weight:600; color:var(--dim); background:transparent; }
        .ks-seg--on{ background:#C8992E; color:#fff; }
        .ks-input{ border:1px solid var(--line); border-radius:.5rem; padding:.4rem .6rem; background:var(--cell); color:var(--txt); font-size:.9rem; }

        .ks-cards{ display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:1rem; }
        .ks-card{ border:1px solid var(--line); border-radius:.9rem; padding:1rem 1.1rem; background:var(--cell);
            box-shadow:0 8px 24px -18px rgba(15,32,56,.25); border-top:3px solid #C8992E; }
        .ks-card--in{ border-top-color:#059669; }
        .ks-card--out{ border-top-color:#d97706; }
        .ks-card--net{ border-top-color:#0F2038; }
        .ks-card--stock{ border-top-color:#C8992E; }
        .ks-card__l{ font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:var(--dim); }
        .ks-card__v{ font-size:1.6rem; font-weight:800; color:var(--txt); margin:.25rem 0 .1rem; letter-spacing:-.01em; }
        .ks-card__s{ font-size:.75rem; color:var(--dim); }
        .ks-card--link{ text-decoration:none; transition:transform .08s ease, box-shadow .08s ease; }
        .ks-card--link:hover{ transform:translateY(-2px); box-shadow:0 14px 30px -16px rgba(15,32,56,.35); }

        .ks-tab{ width:100%; border-collapse:collapse; font-size:.88rem; }
        .ks-tab th, .ks-tab td{ padding:.5rem .6rem; border-bottom:1px solid var(--line); color:var(--txt); }
        .ks-tab th{ font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--dim); text-align:left; }
        .ks-tab .r{ text-align:right; white-space:nowrap; }
        .ks-tab tfoot td{ font-weight:700; border-top:2px solid #C8992E; border-bottom:0; }
        .ks-tab .ks-now td{ background:rgba(200,153,46,.10); font-weight:600; }
        .ks-tab--click tbody tr{ cursor:pointer; }
        .ks-tab--click tbody tr:hover td{ background:rgba(200,153,46,.06); }
        .ks-arrow{ color:var(--dim); opacity:0; transition:opacity .1s; }
        .ks-tab--click tbody tr:hover .ks-arrow{ opacity:1; }
        .ks-badge{ font-size:.72rem; padding:.1rem .45rem; border-radius:.35rem; background:rgba(200,153,46,.14); color:#9a7420; }
        .ks-doklad{ font-weight:700; color:#C8992E; text-decoration:none; }
        .ks-doklad:hover{ text-decoration:underline; }
        .ks-empty{ color:var(--dim); font-size:.9rem; }
    </style>
</x-filament-panels::page>
