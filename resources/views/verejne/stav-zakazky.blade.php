<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Stav zakázky {{ $z->cislo }}</title>
    <style>
        :root {
            --navy: #0F2038; --navy-2: #16294a; --gold: #C8992E; --gold-l: #E8C77C;
            --bg: #f4f5f7; --card: #ffffff; --txt: #1f2937; --dim: #6b7280; --line: #e5e7eb;
            --ok: #059669; --wait: #d97706; --stop: #dc2626; --info: #2563eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--bg); color: var(--txt);
            font: 16px/1.55 -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .wrap { max-width: 460px; margin: 0 auto; padding: 0 16px 40px; }

        .top {
            background: var(--navy); color: #fff; text-align: center;
            padding: 26px 20px 22px; border-bottom: 3px solid var(--gold);
            margin: 0 -16px 20px;
        }
        .top img { height: 54px; width: auto; }
        .top .name { font-weight: 700; letter-spacing: .5px; margin-top: 8px; font-size: 1.05rem; }
        .top .name span { color: var(--gold-l); }

        .card {
            background: var(--card); border: 1px solid var(--line); border-radius: 14px;
            padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(16,32,56,.05);
        }
        .zc { font-size: .8rem; color: var(--dim); text-transform: uppercase; letter-spacing: .06em; }
        .cislo { font-size: 1.6rem; font-weight: 800; color: var(--navy); margin: 2px 0 4px; }
        .zar { color: var(--dim); font-size: .95rem; }

        .stav {
            border-radius: 12px; padding: 16px 16px 14px; margin-top: 16px;
            border: 1px solid var(--line); border-left: 5px solid var(--c, var(--info));
            background: color-mix(in srgb, var(--c, var(--info)) 7%, #fff);
        }
        .stav.t-ok   { --c: var(--ok); }
        .stav.t-wait { --c: var(--wait); }
        .stav.t-stop { --c: var(--stop); }
        .stav.t-info { --c: var(--info); }
        .stav.t-done { --c: var(--dim); }
        .stav h2 { margin: 0 0 4px; font-size: 1.15rem; color: var(--c, var(--info)); }
        .stav p { margin: 0; font-size: .95rem; color: var(--txt); }

        .steps { display: flex; margin: 22px 0 4px; padding: 0; list-style: none; }
        .steps li { flex: 1; text-align: center; position: relative; font-size: .68rem; color: var(--dim); }
        .steps li::before {
            content: ""; display: block; width: 14px; height: 14px; border-radius: 50%;
            background: var(--line); margin: 0 auto 6px; border: 2px solid var(--line);
        }
        .steps li::after {
            content: ""; position: absolute; top: 6px; left: -50%; width: 100%; height: 2px;
            background: var(--line); z-index: 0;
        }
        .steps li:first-child::after { display: none; }
        .steps li.done { color: var(--navy); font-weight: 600; }
        .steps li.done::before { background: var(--gold); border-color: var(--gold); }
        .steps li.done::after { background: var(--gold); }
        .steps li.cur::before { box-shadow: 0 0 0 4px color-mix(in srgb, var(--gold) 25%, transparent); }

        .rows { margin-top: 18px; border-top: 1px solid var(--line); }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: .95rem; }
        .row .k { color: var(--dim); }
        .row .v { font-weight: 600; text-align: right; }
        .row .v.big { font-size: 1.1rem; color: var(--navy); }

        .box {
            border-radius: 12px; padding: 16px; margin-top: 16px; font-size: .95rem;
            background: color-mix(in srgb, var(--ok) 8%, #fff); border: 1px solid color-mix(in srgb, var(--ok) 30%, #fff);
        }
        .box h3 { margin: 0 0 6px; color: var(--ok); font-size: 1rem; }
        .box .adr { white-space: pre-line; color: var(--txt); }
        .box .tel { margin-top: 8px; font-weight: 700; }
        .box .tel a { color: var(--navy); text-decoration: none; }

        .kontakt { text-align: center; color: var(--dim); font-size: .88rem; margin-top: 22px; }
        .kontakt a { color: var(--navy); font-weight: 600; text-decoration: none; }
        .admin { text-align: center; margin-top: 16px; }
        .admin a { font-size: .8rem; color: var(--dim); text-decoration: none; border: 1px solid var(--line); padding: 6px 12px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <img src="{{ rtrim(config('app.url'), '/') }}/images/konzolak-logo-print.png" alt="Konzolák Zlín">
            <div class="name">{{ $firma->nazev ?: 'Konzolák' }} <span>servis</span></div>
        </div>

        <div class="card">
            <div class="zc">Zakázka</div>
            <div class="cislo">{{ $z->cislo }}</div>
            @if ($z->zarizeni)
                <div class="zar">{{ $z->zarizeni->oznaceni }}</div>
            @endif

            <div class="stav t-{{ $tonalita }}">
                <h2>{{ $nadpis }}</h2>
                @if ($popis)<p>{{ $popis }}</p>@endif
            </div>

            @if (! in_array($z->stav, ['storno', 'nerentabilni'], true))
                <ol class="steps">
                    @foreach (['Přijato', 'Diagnostika', 'Oprava', 'Hotovo', 'Vyzvednuto'] as $i => $label)
                        <li class="{{ $i < $krok ? 'done' : ($i === $krok ? 'done cur' : '') }}">{{ $label }}</li>
                    @endforeach
                </ol>
            @endif

            <div class="rows">
                <div class="row">
                    <span class="k">Přijato</span>
                    <span class="v">{{ optional($z->datum_prijeti)->format('d.m.Y') ?: '—' }}</span>
                </div>
                @if ($z->datum_vyrizeni)
                    <div class="row">
                        <span class="k">Vyřízeno</span>
                        <span class="v">{{ $z->datum_vyrizeni->format('d.m.Y') }}</span>
                    </div>
                @endif
                @if (in_array($z->stav, ['hotovo', 'vydano'], true) && $z->cena_celkem > 0)
                    <div class="row">
                        <span class="k">{{ $z->stav === 'vydano' ? 'Zaplaceno' : 'K úhradě' }}</span>
                        <span class="v big">{{ number_format($kUhrade, 0, ',', ' ') }} Kč</span>
                    </div>
                @elseif ($z->predpokladana_cena > 0)
                    <div class="row">
                        <span class="k">Předpokládaná cena</span>
                        <span class="v">{{ number_format((float) $z->predpokladana_cena, 0, ',', ' ') }} Kč</span>
                    </div>
                @endif
                @if ($z->zaruka_mesice && $z->stav === 'vydano')
                    <div class="row">
                        <span class="k">Záruka</span>
                        <span class="v">{{ $z->zaruka_mesice }} měs.</span>
                    </div>
                @endif
            </div>

            @if ($z->stav === 'hotovo')
                <div class="box">
                    <h3>Kde a kdy vyzvednout</h3>
                    @if ($firma->email_vyzvednuti)
                        <div class="adr">{{ trim($firma->email_vyzvednuti) }}</div>
                    @else
                        <div class="adr">{{ trim(($firma->ulice ? $firma->ulice . ', ' : '') . trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? ''))) }}</div>
                    @endif
                    @if ($firma->telefon)
                        <div class="tel">Volejte předem: <a href="tel:{{ preg_replace('/\s+/', '', $firma->telefon) }}">{{ $firma->telefon }}</a></div>
                    @endif
                </div>
            @endif
        </div>

        <div class="kontakt">
            @if ($firma->telefon)<a href="tel:{{ preg_replace('/\s+/', '', $firma->telefon) }}">{{ $firma->telefon }}</a>@endif
            @if ($firma->telefon && $firma->email) &nbsp;·&nbsp; @endif
            @if ($firma->email)<a href="mailto:{{ $firma->email }}">{{ $firma->email }}</a>@endif
        </div>

        <div class="admin">
            <a href="{{ $adminUrl }}">Pro servis: otevřít v systému →</a>
        </div>
    </div>
</body>
</html>
