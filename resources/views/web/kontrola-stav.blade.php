@php
    $tel = \App\Support\Web::telMezinarodne();
@endphp
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stav zakázky {{ $z->cislo }} | {{ $firma->nazev ?? 'Konzolák Zlín' }}</title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#0b1a30">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/konzolak-icon.png">
    <link rel="stylesheet" href="{{ asset('css/web.css') }}?v=25">
</head>
<body style="min-height:100vh;display:flex;flex-direction:column">

    <header style="padding:2.2rem 0 1.6rem;border-bottom:1px solid var(--line)">
        <div class="wrap" style="display:flex;justify-content:center">
            <img src="/images/konzolak-logo-print.png" alt="{{ $firma->nazev ?? 'Konzolák Zlín' }}" style="height:110px;width:auto;max-width:80%">
        </div>
    </header>

    <main style="flex:1">
        <div class="wrap stav-wrap">
            <div class="stav-card">
                <div class="stav-eyebrow">Zakázka</div>
                <div class="stav-cislo">{{ $z->cislo }}</div>
                @if($z->zarizeni)
                    <div class="stav-zar">{{ $z->zarizeni->oznaceni }}</div>
                @endif

                <div class="stav-hl t-{{ $tonalita }}">
                    <h2>{{ $nadpis }}</h2>
                    @if($popis)<p>{{ $popis }}</p>@endif
                </div>

                @if(! in_array($z->stav, ['storno', 'nerentabilni'], true))
                    <ol class="stav-steps">
                        @foreach(['Přijato', 'Diagnostika', 'Oprava', 'Hotovo', 'Vyzvednuto'] as $i => $label)
                            <li class="{{ $i < $krok ? 'done' : ($i === $krok ? 'done cur' : '') }}">{{ $label }}</li>
                        @endforeach
                    </ol>
                @endif

                <div class="stav-rows">
                    <div class="stav-row">
                        <span class="k">Přijato</span>
                        <span class="v">{{ optional($z->datum_prijeti)->format('d.m.Y') ?: '—' }}</span>
                    </div>
                    @if($z->datum_vyrizeni)
                        <div class="stav-row">
                            <span class="k">Vyřízeno</span>
                            <span class="v">{{ $z->datum_vyrizeni->format('d.m.Y') }}</span>
                        </div>
                    @endif
                    @if(in_array($z->stav, ['hotovo', 'vydano'], true) && $z->cena_celkem > 0)
                        <div class="stav-row">
                            <span class="k">{{ $z->stav === 'vydano' ? 'Zaplaceno' : 'K úhradě' }}</span>
                            <span class="v big">{{ number_format($kUhrade, 0, ',', ' ') }} Kč</span>
                        </div>
                    @elseif($z->predpokladana_cena > 0)
                        <div class="stav-row">
                            <span class="k">Předpokládaná cena</span>
                            <span class="v">{{ number_format((float) $z->predpokladana_cena, 0, ',', ' ') }} Kč</span>
                        </div>
                    @endif
                    @if($z->zaruka_mesice && $z->stav === 'vydano')
                        <div class="stav-row">
                            <span class="k">Záruka</span>
                            <span class="v">{{ $z->zaruka_mesice }} měs.</span>
                        </div>
                    @endif
                    @if($z->zpusob_vydani === 'odeslani' && $z->odeslano_datum)
                        <div class="stav-row">
                            <span class="k">Odesláno</span>
                            <span class="v">{{ $z->odeslano_datum->format('d.m.Y') }}</span>
                        </div>
                    @endif
                    @if($z->zpusob_vydani === 'odeslani' && $z->sledovaci_cislo)
                        <div class="stav-row">
                            <span class="k">Sledovací číslo</span>
                            <span class="v">{{ $z->sledovaci_cislo }}</span>
                        </div>
                    @endif
                </div>

                @if($z->stav === 'hotovo' && $z->zpusob_vydani === 'odeslani')
                    <div class="stav-box">
                        <h3>Odesíláme zpět</h3>
                        <div class="adr">
                            @if($z->dopravce)Pošleme přes {{ $z->dopravce }}.@else Brzy zařízení odešleme zpět. @endif
                            Jakmile bude na cestě, doplníme sem sledovací číslo.
                        </div>
                    </div>
                @elseif($z->stav === 'hotovo')
                    <div class="stav-box">
                        <h3>Kde a kdy vyzvednout</h3>
                        @if($firma->email_vyzvednuti)
                            <div class="adr">{{ trim($firma->email_vyzvednuti) }}</div>
                        @elseif($firma->ulice)
                            <div class="adr">{{ $firma->ulice }}, {{ trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? '')) }}</div>
                        @endif
                        @if($firma->telefon)
                            <p style="margin:.6rem 0 0">Volejte předem:
                                <a href="tel:+{{ $tel }}">{{ $firma->telefon }}</a></p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </main>

    <footer style="padding:1.4rem 0;border-top:1px solid var(--line);text-align:center;color:var(--muted);font-size:.9rem">
        <div class="wrap">
            {{ $firma->nazev ?? 'Konzolák Zlín' }}
            @if($firma->telefon) · <a href="tel:+{{ $tel }}">{{ $firma->telefon }}</a>@endif
            @if($firma->email) · <a href="mailto:{{ $firma->email }}">{{ $firma->email }}</a>@endif
            <br>
            <a href="{{ route('web.home') }}" style="color:var(--muted);text-decoration:underline">www.konzolak.com</a>
        </div>
    </footer>

</body>
</html>
