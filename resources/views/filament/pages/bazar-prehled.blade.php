<x-filament-panels::page>
    @php($f = fn ($n) => number_format((float) $n, 0, ',', ' ') . ' Kč')

    <div class="bz">
        <div class="bz-bar">
            <label>Rok</label>
            <select wire:model.live="rok" class="bz-input">
                @foreach ($this->roky as $r)
                    <option value="{{ $r }}">{{ $r }}</option>
                @endforeach
            </select>
            <span class="bz-note">Interní přehled – nezapočítává se do peněžního deníku ani do podkladů pro daně.</span>
        </div>

        {{-- Souhrn --}}
        <x-filament::section>
            <x-slot name="heading">Souhrn · {{ $this->rok }}</x-slot>
            <div class="bz-cards">
                <div class="bz-card">
                    <span class="bz-l">Nakoupeno ({{ $this->souhrn['pocet_nakup'] }}×)</span>
                    <span class="bz-v">{{ $f($this->souhrn['nakoupeno']) }}</span>
                </div>
                <div class="bz-card">
                    <span class="bz-l">Díly a náklady</span>
                    <span class="bz-v">{{ $f($this->souhrn['dily']) }}</span>
                </div>
                <div class="bz-card">
                    <span class="bz-l">Investováno celkem</span>
                    <span class="bz-v">{{ $f($this->souhrn['investovano']) }}</span>
                </div>
                <div class="bz-card">
                    <span class="bz-l">Prodáno ({{ $this->souhrn['pocet_prodej'] }}×)</span>
                    <span class="bz-v">{{ $f($this->souhrn['trzba']) }}</span>
                </div>
                <div class="bz-card {{ $this->souhrn['zisk'] < 0 ? 'bz-neg' : 'bz-pos' }}">
                    <span class="bz-l">Zisk z prodaných</span>
                    <span class="bz-v">{{ $f($this->souhrn['zisk']) }}</span>
                </div>
                <div class="bz-card">
                    <span class="bz-l">Skladem (neprodáno)</span>
                    <span class="bz-v">{{ $this->souhrn['skladem'] }} ks</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Nákup vs. prodej po měsících --}}
        <x-filament::section>
            <x-slot name="heading">Nákup vs. prodej po měsících · {{ $this->rok }}</x-slot>
            <div class="bz-legend">
                <span><i class="bz-sw bz-sw--nakup"></i> Nákup</span>
                <span><i class="bz-sw bz-sw--prodej"></i> Prodej</span>
            </div>
            <div class="bz-months">
                @foreach ($this->mesice['radky'] as $m)
                    <div class="bz-month">
                        <div class="bz-pair">
                            <div class="bz-col bz-col--nakup" style="height: {{ max(2, round($m['nakup'] / $this->mesice['max'] * 100)) }}%"
                                 title="Nákup {{ $f($m['nakup']) }}"></div>
                            <div class="bz-col bz-col--prodej" style="height: {{ max(2, round($m['prodej'] / $this->mesice['max'] * 100)) }}%"
                                 title="Prodej {{ $f($m['prodej']) }}"></div>
                        </div>
                        <span class="bz-mname">{{ $m['nazev'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Prodané kusy --}}
        <x-filament::section>
            <x-slot name="heading">Prodané kusy · {{ $this->rok }}</x-slot>
            <table class="bz-table">
                <thead>
                    <tr>
                        <th>Datum</th><th>Zařízení</th>
                        <th class="bz-num">Nákup</th><th class="bz-num">Díly</th>
                        <th class="bz-num">Prodej</th><th class="bz-num">Zisk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->prodeje as $p)
                        <tr>
                            <td>{{ $p['datum'] }}</td>
                            <td>{{ $p['nazev'] }}</td>
                            <td class="bz-num">{{ $f($p['nakup']) }}</td>
                            <td class="bz-num">{{ $f($p['dily']) }}</td>
                            <td class="bz-num">{{ $f($p['prodej']) }}</td>
                            <td class="bz-num {{ $p['zisk'] < 0 ? 'bz-tneg' : 'bz-tpos' }}">{{ $f($p['zisk']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="bz-empty">Za rok {{ $this->rok }} zatím nic prodáno.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>
    </div>

    <style>
        .bz{ --line:#e5e7eb; --dim:#6b7280; --cell:#fff; --txt:#1f2937; display:flex; flex-direction:column; gap:1.25rem; }
        :is(.dark) .bz{ --line:#374151; --dim:#9ca3af; --cell:#1f2937; --txt:#f3f4f6; }
        .bz-bar{ display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
        .bz-bar label{ font-size:.85rem; color:var(--dim); font-weight:600; }
        .bz-input{ border:1px solid var(--line); border-radius:.5rem; padding:.35rem .7rem; background:var(--cell); color:var(--txt); }
        .bz-note{ font-size:.78rem; color:var(--dim); }
        .bz-cards{ display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:.75rem; }
        .bz-card{ border:1px solid var(--line); border-radius:.7rem; padding:.75rem .9rem; background:var(--cell);
            display:flex; flex-direction:column; gap:.15rem; border-top:3px solid #94a3b8; }
        .bz-card.bz-pos{ border-top-color:#059669; } .bz-card.bz-neg{ border-top-color:#dc2626; }
        .bz-l{ font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; color:var(--dim); }
        .bz-v{ font-size:1.15rem; font-weight:800; color:var(--txt); }
        .bz-legend{ display:flex; gap:1.2rem; font-size:.8rem; color:var(--dim); margin-bottom:.6rem; }
        .bz-sw{ display:inline-block; width:.8rem; height:.8rem; border-radius:.2rem; vertical-align:-1px; }
        .bz-sw--nakup{ background:#C8992E; } .bz-sw--prodej{ background:#0ea5e9; }
        .bz-months{ display:flex; align-items:flex-end; gap:.5rem; height:10rem; padding-top:.5rem; }
        .bz-month{ flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end; }
        .bz-pair{ display:flex; align-items:flex-end; gap:2px; height:100%; width:100%; justify-content:center; }
        .bz-col{ width:42%; min-height:2px; border-radius:.2rem .2rem 0 0; }
        .bz-col--nakup{ background:#C8992E; } .bz-col--prodej{ background:#0ea5e9; }
        .bz-mname{ font-size:.68rem; color:var(--dim); margin-top:.3rem; }
        .bz-table{ width:100%; border-collapse:collapse; font-size:.85rem; color:var(--txt); }
        .bz-table th, .bz-table td{ padding:.4rem .5rem; border-bottom:1px solid var(--line); text-align:left; }
        .bz-table th{ font-size:.72rem; text-transform:uppercase; letter-spacing:.02em; color:var(--dim); }
        .bz-num{ text-align:right; font-variant-numeric:tabular-nums; }
        .bz-tpos{ color:#059669; font-weight:700; } .bz-tneg{ color:#dc2626; font-weight:700; }
        .bz-empty{ color:var(--dim); text-align:center; padding:1rem; }
    </style>
</x-filament-panels::page>
