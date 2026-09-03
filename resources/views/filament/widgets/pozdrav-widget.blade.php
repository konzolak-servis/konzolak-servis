<x-filament-widgets::widget>
    <div
        x-data="{
            cas: '',
            datum: @js($datum),
            tik() {
                const d = new Date();
                this.cas = d.toLocaleTimeString('cs-CZ', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            },
            init() { this.tik(); setInterval(() => this.tik(), 1000); }
        }"
        class="ks-hero"
    >
        <div class="ks-hero__left">
            <div class="ks-hero__hello">{{ $pozdrav }}<span>,</span> {{ $jmeno ?: 'vítej' }} 👋</div>
            <div class="ks-hero__date">{{ $datum }}</div>
        </div>

        <div class="ks-hero__clock">
            <span x-text="cas">--:--:--</span>
        </div>

        <div class="ks-hero__weather">
            @if ($pocasi)
                <div class="ks-hero__temp">
                    <span class="ks-hero__wicon">{{ $pocasi['ikona'] }}</span>
                    <span>{{ $pocasi['teplota'] }} °C</span>
                </div>
                <div class="ks-hero__wmeta">
                    {{ $pocasi['popis'] }} · pocitově {{ $pocasi['pocit'] }} °C · vítr {{ $pocasi['vitr'] }} km/h
                </div>
                <div class="ks-hero__wmeta ks-hero__wmeta--dim">{{ $pocasi['misto'] }}</div>
            @else
                <div class="ks-hero__wmeta">Počasí se nepodařilo načíst</div>
            @endif
        </div>
    </div>

    <style>
        /* světlý = perleťově béžový, lehce do modré */
        .ks-hero{
            --hero-fg:#243447; --hero-dim:#6b7280; --hero-dim2:#9aa0aa; --hero-gold:#A9781F;
            display:flex; flex-wrap:wrap; align-items:center; gap:1.5rem;
            padding:1.4rem 1.6rem; border-radius:1rem; color:var(--hero-fg);
            background:linear-gradient(125deg, #FBF7EC 0%, #F1EFEA 45%, #E7ECF3 100%);
            border:1px solid rgba(163,143,102,.28);
            box-shadow:0 10px 30px -16px rgba(36,52,71,.28);
        }
        :is(.dark) .ks-hero{
            --hero-fg:#F5F1E6; --hero-dim:#AEB4C2; --hero-dim2:#7E8796; --hero-gold:#C8992E;
            background:linear-gradient(120deg,#0F2038 0%,#1B3A5B 60%,#243b53 100%);
            border-color:transparent;
            box-shadow:0 10px 30px -12px rgba(15,32,56,.55);
        }
        .ks-hero__left{ flex:1 1 260px; }
        .ks-hero__hello{ font-size:1.55rem; font-weight:700; letter-spacing:-.01em; }
        .ks-hero__hello span{ color:var(--hero-gold); }
        .ks-hero__date{ margin-top:.15rem; font-size:.95rem; color:var(--hero-dim); text-transform:capitalize; }
        .ks-hero__clock{
            flex:0 0 auto; font-variant-numeric:tabular-nums; font-weight:700;
            font-size:2.4rem; color:var(--hero-gold); letter-spacing:.02em;
        }
        .ks-hero__weather{ flex:1 1 220px; text-align:right; }
        .ks-hero__temp{ display:flex; justify-content:flex-end; align-items:center; gap:.5rem; font-size:1.6rem; font-weight:700; }
        .ks-hero__wicon{ font-size:1.7rem; }
        .ks-hero__wmeta{ font-size:.85rem; color:var(--hero-dim); margin-top:.15rem; }
        .ks-hero__wmeta--dim{ color:var(--hero-dim2); }
        @media (max-width:720px){
            .ks-hero{ flex-direction:column; text-align:center; gap:1rem; padding:1.3rem 1rem; }
            .ks-hero__left{ flex:none; width:100%; }
            .ks-hero__hello{ font-size:1.3rem; line-height:1.3; }
            .ks-hero__date{ font-size:.9rem; }
            .ks-hero__clock{ flex:none; width:100%; text-align:center; font-size:2.9rem; }
            .ks-hero__weather{ flex:none; width:100%; text-align:center; }
            .ks-hero__temp{ justify-content:center; }
        }
    </style>
</x-filament-widgets::widget>
