<x-filament-panels::page>
    @php($f = fn ($n) => number_format((float) $n, 0, ',', ' ') . ' Kč')

    <div class="pr">
        <div class="pr-bar">
            <label>Rok</label>
            <select wire:model.live="rok" class="pr-input">
                @foreach ($this->roky as $r)
                    <option value="{{ $r }}">{{ $r }}</option>
                @endforeach
            </select>
        </div>

        {{-- Obrat podle činnosti --}}
        <x-filament::section>
            <x-slot name="heading">Obrat podle činnosti · {{ $rok }}</x-slot>
            <x-slot name="headerEnd"><span class="pr-total">{{ $f($this->obrat['celkem']) }}</span></x-slot>
            @forelse ($this->obrat['radky'] as $r)
                <div class="pr-row">
                    <div class="pr-row-top">
                        <span>{{ $r['nazev'] }} <small>({{ $r['pocet'] }}×)</small></span>
                        <strong>{{ $f($r['castka']) }}</strong>
                    </div>
                    <div class="pr-track"><div class="pr-fill" style="width: {{ round($r['castka'] / $this->obrat['max'] * 100) }}%"></div></div>
                </div>
            @empty
                <p class="pr-empty">Za rok {{ $rok }} zatím žádný obrat.</p>
            @endforelse
        </x-filament::section>

        {{-- Bazar --}}
        <x-filament::section>
            <x-slot name="heading">Bazar – výkup / prodej · {{ $rok }}</x-slot>
            <div class="pr-cards">
                <div class="pr-card"><span class="pr-l">Výkup ({{ $this->bazar['pocet_vykup'] }}×)</span><span class="pr-v">{{ $f($this->bazar['vykup']) }}</span></div>
                <div class="pr-card"><span class="pr-l">Prodej ({{ $this->bazar['pocet_prodej'] }}×)</span><span class="pr-v">{{ $f($this->bazar['prodej']) }}</span></div>
                <div class="pr-card {{ $this->bazar['zisk'] < 0 ? 'pr-neg' : 'pr-pos' }}">
                    <span class="pr-l">Hrubá marže</span><span class="pr-v">{{ $f($this->bazar['zisk']) }}</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Nejčastější opravy --}}
        <x-filament::section>
            <x-slot name="heading">Nejčastější práce · {{ $rok }}</x-slot>
            @forelse ($this->nejcastejsi['radky'] as $r)
                <div class="pr-row">
                    <div class="pr-row-top">
                        <span>{{ $r['nazev'] }}</span>
                        <strong>{{ $r['pocet'] }}× &nbsp;·&nbsp; {{ $f($r['castka']) }}</strong>
                    </div>
                    <div class="pr-track"><div class="pr-fill pr-fill--alt" style="width: {{ round($r['pocet'] / $this->nejcastejsi['max'] * 100) }}%"></div></div>
                </div>
            @empty
                <p class="pr-empty">Žádné práce za rok {{ $rok }}.</p>
            @endforelse
        </x-filament::section>

        {{-- Obrat po měsících --}}
        <x-filament::section>
            <x-slot name="heading">Obrat po měsících · {{ $rok }}</x-slot>
            <div class="pr-months">
                @foreach ($this->mesice['radky'] as $m)
                    <div class="pr-month">
                        <div class="pr-col" style="height: {{ max(3, round($m['castka'] / $this->mesice['max'] * 100)) }}%"
                             title="{{ $f($m['castka']) }}"></div>
                        <span class="pr-mname">{{ $m['nazev'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>

    <style>
        .pr{ --line:#e5e7eb; --dim:#6b7280; --cell:#fff; --txt:#1f2937; display:flex; flex-direction:column; gap:1.25rem; }
        :is(.dark) .pr{ --line:#374151; --dim:#9ca3af; --cell:#1f2937; --txt:#f3f4f6; }
        .pr-bar{ display:flex; align-items:center; gap:.6rem; }
        .pr-bar label{ font-size:.85rem; color:var(--dim); font-weight:600; }
        .pr-input{ border:1px solid var(--line); border-radius:.5rem; padding:.35rem .7rem; background:var(--cell); color:var(--txt); }
        .pr-total{ font-weight:800; color:#C8992E; }
        .pr-row{ padding:.45rem 0; border-bottom:1px solid var(--line); }
        .pr-row:last-child{ border-bottom:0; }
        .pr-row-top{ display:flex; justify-content:space-between; font-size:.9rem; color:var(--txt); margin-bottom:.3rem; }
        .pr-row-top small{ color:var(--dim); }
        .pr-track{ height:.55rem; border-radius:.4rem; background:var(--line); overflow:hidden; }
        .pr-fill{ height:100%; background:#C8992E; }
        .pr-fill--alt{ background:#0ea5e9; }
        .pr-cards{ display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; }
        .pr-card{ border:1px solid var(--line); border-radius:.7rem; padding:.75rem .9rem; background:var(--cell);
            display:flex; flex-direction:column; gap:.15rem; border-top:3px solid #94a3b8; }
        .pr-card.pr-pos{ border-top-color:#059669; } .pr-card.pr-neg{ border-top-color:#dc2626; }
        .pr-l{ font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; color:var(--dim); }
        .pr-v{ font-size:1.15rem; font-weight:800; color:var(--txt); }
        .pr-empty{ color:var(--dim); font-size:.9rem; }
        .pr-months{ display:flex; align-items:flex-end; gap:.5rem; height:9rem; padding-top:.5rem; }
        .pr-month{ flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end; }
        .pr-col{ width:70%; min-height:3px; background:#C8992E; border-radius:.25rem .25rem 0 0; }
        .pr-mname{ font-size:.68rem; color:var(--dim); margin-top:.3rem; }
    </style>
</x-filament-panels::page>
