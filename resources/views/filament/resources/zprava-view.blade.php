@php
    $vlakno = $this->getVlakno();
    $zakazkaUrl = $this->getZakazkaUrl();
    $aktualni = $this->record;
@endphp

<x-filament-panels::page>
    {{-- kontext: zakázka + schránka --}}
    <div class="ks-mail-top">
        <div>
            @if ($aktualni->zakazka && $zakazkaUrl)
                <a href="{{ $zakazkaUrl }}" class="ks-mail-zak">
                    🧾 Zakázka {{ $aktualni->zakazka->cislo }}
                    @if ($aktualni->zakazka->zakaznik) · {{ $aktualni->zakazka->zakaznik->nazev }}@endif
                </a>
            @else
                <span class="ks-mail-zak ks-mail-zak--none">Bez zakázky</span>
            @endif
        </div>
        <div class="ks-mail-schr">
            @if ($aktualni->schranka)schránka {{ $aktualni->schranka }}@endif
        </div>
    </div>

    {{-- vlákno --}}
    <div class="ks-thread">
        @foreach ($vlakno as $m)
            @php $odchozi = $m->smer === 'out'; @endphp
            <div @class([
                'ks-msg',
                'ks-msg--out' => $odchozi,
                'ks-msg--current' => $m->id === $aktualni->id,
            ])>
                <div class="ks-msg-head">
                    <div class="ks-msg-who">
                        @if ($odchozi)
                            <span class="ks-msg-dir ks-msg-dir--out">Odpověď</span>
                            <span class="ks-msg-addr">→ {{ $m->pro }}</span>
                        @else
                            <span class="ks-msg-dir ks-msg-dir--in">Přijato</span>
                            <span class="ks-msg-addr">{{ $m->od_jmeno ? $m->od_jmeno . ' · ' : '' }}{{ $m->od }}</span>
                        @endif
                    </div>
                    <div class="ks-msg-meta">
                        <span class="ks-msg-date">{{ $m->datum?->format('d.m.Y H:i') }}</span>
                        <button type="button" class="ks-msg-del"
                            wire:click="smazatZpravu({{ $m->id }})"
                            wire:confirm="Opravdu smazat tuto zprávu? Nelze vzít zpět.">smazat</button>
                    </div>
                </div>

                @if ($m->predmet && $m->predmet !== $aktualni->predmet)
                    <div class="ks-msg-subj">{{ $m->predmet }}</div>
                @endif

                <div class="ks-msg-body">{{ $m->telo_text ?: strip_tags((string) $m->telo_html) ?: '(prázdná zpráva)' }}</div>

                @if (is_array($m->prilohy) && count($m->prilohy))
                    <div class="ks-msg-att">
                        @foreach (array_values($m->prilohy) as $i => $p)
                            <a class="ks-att" href="{{ route('posta.priloha', ['zprava' => $m->id, 'index' => $i]) }}">
                                📎 {{ $p['nazev'] ?? 'příloha' }}
                                @if (! empty($p['velikost']))
                                    <span class="ks-att-size">{{ number_format($p['velikost'] / 1024, 0, ',', ' ') }} kB</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <style>
        .ks-mail-top { display:flex; justify-content:space-between; align-items:center; gap:1rem;
            padding:.6rem .9rem; background:rgba(200,153,46,.08); border:1px solid rgba(200,153,46,.25);
            border-radius:.6rem; font-size:.85rem; flex-wrap:wrap; }
        .ks-mail-zak { color:#0F2038; font-weight:700; text-decoration:none; }
        :is(.dark) .ks-mail-zak { color:#E8C77C; }
        .ks-mail-zak:hover { text-decoration:underline; }
        .ks-mail-zak--none { color:#9ca3af; font-weight:500; }
        .ks-mail-schr { color:#8a8578; font-size:.8rem; }

        .ks-thread { display:flex; flex-direction:column; gap:.85rem; margin-top:1rem; }
        .ks-msg { border:1px solid #e5e7eb; border-radius:.7rem; padding:.85rem 1rem; background:#fff; }
        :is(.dark) .ks-msg { background:#1c2431; border-color:#2b3440; }
        .ks-msg--out { margin-left:2.5rem; background:#f6faf7; border-color:#d7e8dc; }
        :is(.dark) .ks-msg--out { background:#17251d; border-color:#274233; }
        .ks-msg--current { box-shadow:0 0 0 3px rgba(200,153,46,.35); }

        .ks-msg-head { display:flex; justify-content:space-between; align-items:baseline; gap:1rem; flex-wrap:wrap; }
        .ks-msg-who { display:flex; align-items:baseline; gap:.5rem; flex-wrap:wrap; min-width:0; }
        .ks-msg-dir { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; font-weight:700;
            padding:.1rem .4rem; border-radius:.3rem; }
        .ks-msg-dir--in { background:rgba(37,99,235,.12); color:#1d4ed8; }
        .ks-msg-dir--out { background:rgba(5,150,105,.14); color:#047857; }
        :is(.dark) .ks-msg-dir--in { color:#93c5fd; }
        :is(.dark) .ks-msg-dir--out { color:#6ee7b7; }
        .ks-msg-addr { font-size:.85rem; color:#374151; word-break:break-all; }
        :is(.dark) .ks-msg-addr { color:#d1d5db; }
        .ks-msg-meta { display:flex; align-items:center; gap:.6rem; white-space:nowrap; }
        .ks-msg-date { font-size:.78rem; color:#9ca3af; }
        .ks-msg-del { font-size:.72rem; color:#b91c1c; background:none; border:0; cursor:pointer;
            padding:.1rem .3rem; border-radius:.3rem; opacity:.55; }
        .ks-msg-del:hover { opacity:1; background:rgba(185,28,28,.1); text-decoration:underline; }
        :is(.dark) .ks-msg-del { color:#fca5a5; }
        .ks-msg-subj { margin-top:.4rem; font-size:.8rem; color:#6b7280; font-style:italic; }
        .ks-msg-body { margin-top:.6rem; white-space:pre-wrap; word-wrap:break-word; font-size:.92rem;
            line-height:1.55; color:#1f2937; }
        :is(.dark) .ks-msg-body { color:#e5e7eb; }

        .ks-msg-att { margin-top:.7rem; padding-top:.6rem; border-top:1px dashed #e5e7eb;
            display:flex; flex-wrap:wrap; gap:.4rem; }
        :is(.dark) .ks-msg-att { border-color:#2b3440; }
        .ks-att { display:inline-flex; align-items:center; gap:.35rem; font-size:.82rem; text-decoration:none;
            padding:.25rem .55rem; border:1px solid #d7ddE6; border-radius:.4rem; color:#1d4ed8; background:#f8fafc; }
        :is(.dark) .ks-att { color:#93c5fd; background:#17202b; border-color:#2b3440; }
        .ks-att:hover { text-decoration:underline; }
        .ks-att-size { color:#9ca3af; font-size:.72rem; }
    </style>
</x-filament-panels::page>
