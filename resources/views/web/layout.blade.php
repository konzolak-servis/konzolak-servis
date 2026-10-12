@php
    use App\Support\Web;
    $F = $firma ?? Web::firma();
    $tel = Web::telMezinarodne();
    $adminUrl = rtrim(config('app.url'), '/') . '/admin';
    $nahledWebu = filled(config('web.gate_heslo')) && session('web_gate_ok');
    $K = Web::kontakty();
@endphp
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', ($F->nazev ?? 'Konzolák Zlín') . ' – servis herních konzolí a PC ve Zlíně')</title>
    <meta name="description" content="@yield('desc', 'Servis a opravy herních konzolí PlayStation, Xbox, Nintendo a PC ve Zlíně. Diagnostika zdarma, cena předem odsouhlasená.')">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <meta property="og:title" content="@yield('title', $F->nazev ?? 'Konzolák Zlín')">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="#0b1a30">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/konzolak-icon.png">
    <link rel="apple-touch-icon" href="/images/konzolak-icon.png">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/web.css') }}?v=34">

    {{-- Strukturovaná data pro Google (Knowledge Panel, místní vyhledávání) – na každé stránce.
         Hodnocení/recenze jsou navíc jen na stránce Reference, kde jsou vidět i jako text
         (Google vyžaduje, aby značkovaná recenze byla na stránce opravdu zobrazená).
         Pole se sestavuje v PHP bloku předem, ne přímo v echo výrazu – Blade jinak
         literální klíč pole "kontext" spletl s vlastní direktivou a rozbil výstup. --}}
    @php
        $schemaFirmy = [
            '@context' => 'https://schema.org',
            '@type' => 'ElectronicsStore',
            'name' => $F->nazev ?: 'Konzolák Zlín',
            'image' => rtrim(config('app.url'), '/') . '/images/konzolak-logo-print.png',
            'url' => url('/'),
            'telephone' => $tel ? '+' . $tel : null,
            'email' => $F->email ?: null,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $F->ulice ?: null,
                'addressLocality' => $F->mesto ?: 'Zlín',
                'postalCode' => $F->psc ?: null,
                'addressCountry' => 'CZ',
            ],
            'openingHoursSpecification' => [[
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'opens' => '09:00',
                'closes' => '17:00',
            ]],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($schemaFirmy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

    @stack('head')
</head>
<body>
@if($nahledWebu)
<div class="preview-bar">
    <span>Náhled webu – zatím nezveřejněno</span>
    <a href="{{ route('web.odhlasit') }}">Odhlásit náhled</a>
</div>
@endif
<div class="topbar">
    <div class="wrap topbar__in">
        <span>Otevřeno {{ Web::oteviraciDoba() }}</span>
        <span class="topbar__sep">·</span>
        <span>Osobně ve Zlíně, zasílám po celé ČR</span>
        @if($tel)<a class="topbar__tel" href="tel:+{{ $tel }}">{{ $F->telefon }}</a>@endif
    </div>
</div>
<header class="hdr">
    <div class="wrap hdr__in">
        <a href="{{ route('web.home') }}" class="hdr__logo">
            <img src="/images/konzolak-logo-print.png" alt="{{ $F->nazev ?? 'Konzolák Zlín' }}">
        </a>
        <button class="navtoggle" aria-label="Menu" aria-expanded="false">☰</button>
        <nav class="nav" id="nav">
            @php $r = request()->route()?->getName() ?? ''; @endphp
            <a href="{{ route('web.opravy') }}" @if(str_starts_with($r,'web.opravy')) aria-current="page" @endif>Opravujeme</a>
            <a href="{{ route('web.cenik') }}" @if($r==='web.cenik') aria-current="page" @endif>Ceník</a>
            <a href="{{ route('web.zasilani') }}" @if($r==='web.zasilani') aria-current="page" @endif>Zaslání zařízení</a>
            <a href="{{ route('web.kontrola') }}" @if(str_starts_with($r,'web.kontrola')) aria-current="page" @endif>Kontrola zakázky</a>
            <a href="{{ route('web.kontakt') }}" @if($r==='web.kontakt') aria-current="page" @endif>Kontakt</a>
            <details class="nav__more">
                <summary>Více</summary>
                <div class="nav__panel">
                    <a href="{{ route('web.jak-to-funguje') }}" @if($r==='web.jak-to-funguje') aria-current="page" @endif>Jak to funguje</a>
                    <a href="{{ route('web.vykup') }}" @if($r==='web.vykup') aria-current="page" @endif>Výkup zařízení</a>
                    <a href="{{ route('web.reference') }}" @if($r==='web.reference') aria-current="page" @endif>Reference</a>
                    <a href="{{ route('web.faq') }}" @if($r==='web.faq') aria-current="page" @endif>Časté dotazy</a>
                    <a href="{{ route('web.pravni', 'reklamacni-rad') }}">Reklamační řád</a>
                </div>
            </details>
        </nav>
        <div class="hdr__cta">
            @if($tel)<a class="hdr__tel" href="tel:+{{ $tel }}">{{ $F->telefon }}</a>@endif
            <a class="btn btn--dark btn--sm" href="{{ $adminUrl }}">Přihlášení do servisu</a>
            <a class="btn btn--primary btn--sm" href="{{ route('web.kontakt') }}#poptavka">Objednat opravu</a>
        </div>
    </div>
</header>

@yield('body')

<footer class="ftr">
    <div class="wrap ftr__in">
        <div class="ftr__brand">
            <img src="/images/konzolak-logo-print.png" alt="{{ $F->nazev ?? 'Konzolák Zlín' }}">
            <p style="margin:0">Servis herních konzolí, notebooků a počítačů ve Zlíně.
                Osobní přístup, cena vždy předem odsouhlasená.</p>
            <p style="margin:.6rem 0 0">
                @if($F->telefon)<a href="tel:+{{ $tel }}">{{ $F->telefon }}</a>@endif
                @if($F->email)<a href="mailto:{{ $F->email }}">{{ $F->email }}</a>@endif
                <a href="{{ \App\Support\Web::facebookUrl() }}" target="_blank" rel="noopener">Facebook</a>
            </p>
        </div>
        <div>
            <h4>Web</h4>
            <a href="{{ route('web.opravy') }}">Opravujeme</a>
            <a href="{{ route('web.cenik') }}">Ceník</a>
            <a href="{{ route('web.jak-to-funguje') }}">Jak to funguje</a>
            <a href="{{ route('web.zasilani') }}">Zaslání zařízení</a>
            <a href="{{ route('web.vykup') }}">Výkup zařízení</a>
            <a href="{{ route('web.kontrola') }}">Kontrola zakázky</a>
            <a href="{{ route('web.kontakt') }}">Kontakt</a>
        </div>
        <div>
            <h4>Informace</h4>
            <a href="{{ route('web.pravni', 'ochrana-osobnich-udaju') }}">Ochrana osobních údajů</a>
            <a href="{{ route('web.pravni', 'reklamacni-rad') }}">Reklamační řád</a>
            <a href="{{ route('web.pravni', 'cookies') }}">Cookies</a>
            <a href="{{ $adminUrl }}">Přihlášení do servisu</a>
            @if($nahledWebu)<a href="{{ route('web.odhlasit') }}">Odhlásit náhled webu</a>@endif
        </div>
    </div>
    <div class="wrap ftr__legal">
        Provozovatel: {{ $F->nazev ?? 'Konzolák Zlín' }}{{ $F->ico ? ', IČO ' . $F->ico : '' }}{{ ($F->platce_dph ?? false) ? '' : ', neplátce DPH' }}.
        {{ $F->ulice ? $F->ulice . ', ' . trim(($F->psc ?? '') . ' ' . ($F->mesto ?? '')) . '.' : '' }}
        Podnikatel zapsaný v živnostenském rejstříku. Dozorový orgán: Česká obchodní inspekce (coi.gov.cz).
    </div>
</footer>

<nav class="quickbar" aria-label="Rychlý kontakt">
    @if($K['tel'])<a href="{{ $K['tel'] }}">Zavolat</a>@endif
    @if($K['whatsapp'])<a href="{{ $K['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp</a>@endif
    <a href="{{ $K['messenger'] }}" target="_blank" rel="noopener">Messenger</a>
    <a href="{{ route('web.kontakt') }}#poptavka" class="quickbar__main">Poptávka</a>
</nav>

@if(\App\Http\Controllers\Web\ChatController::zapnuto())
<template id="ks-mascot">
    <svg class="mascot" viewBox="70 110 370 410" aria-hidden="true" focusable="false">
        <g class="mascot__all">
            <polygon class="mascot__cap" points="101,131 228,242 205,290 180,286 96,238"/>
            <polygon class="mascot__body" points="228,242 272,231 339,239 379,263 413,308 413,338 360,338 287,294 217,334 209,427 287,468 357,426 359,414 316,413 279,426 279,362 415,362 415,462 325,512 107,512 150,444 130,441 152,395 116,392 116,362 157,335 180,286 150,258 200,222"/>
            <polygon class="mascot__beard" points="157,335 116,362 116,392 152,395 130,441 150,444 107,512 175,512 196,446 200,400 200,340"/>
            <g class="mascot__eye"><circle cx="192" cy="334" r="14.3" class="mascot__eyew"/><circle cx="189.5" cy="333.3" r="7.3" class="mascot__pupil"/><circle cx="186.5" cy="330" r="2.4" class="mascot__glint"/></g>
            <path class="mascot__smile" d="M142.5 420.5 Q166.5 445.5 190.5 421.5"/>
            <ellipse class="mascot__mouth" cx="166" cy="428" rx="17" ry="10"/>
        </g>
    </svg>
</template>
<div id="ks-chat" class="kc" data-url="{{ route('web.chat') }}" data-state="idle">
    <div class="kc__teaser" hidden>
        <span>Něco nefunguje? Zeptej se mě!</span>
        <button type="button" class="kc__teaser-x" aria-label="Zavřít">×</button>
    </div>
    <button type="button" class="kc__fab" aria-expanded="false" aria-controls="ks-chat-panel">
        <span class="kc__avatar" data-mascot></span><span class="kc__fab-txt">Zeptat se asistenta</span>
    </button>
    <section class="kc__panel" id="ks-chat-panel" role="dialog" aria-label="AI asistent" hidden>
        <header class="kc__head">
            <span class="kc__avatar kc__avatar--sm" data-mascot></span>
            <div class="kc__who"><b>Asistent Konzolák</b><span class="kc__status">virtuální asistent (AI)</span></div>
            <button type="button" class="kc__close" aria-label="Zavřít chat">×</button>
        </header>
        <p class="kc__note">Píšete s umělou inteligencí. Neposílejte hesla ani čísla karet. Zprávy zpracovává poskytovatel AI (USA),
            konverzace neukládáme. <a href="{{ route('web.pravni', 'ochrana-osobnich-udaju') }}">Více</a></p>
        <div class="kc__list" aria-live="polite"></div>
        <form class="kc__form">
            <textarea class="kc__input" rows="1" maxlength="1000" placeholder="Napište dotaz…" aria-label="Vaše zpráva"></textarea>
            <button class="kc__send" type="submit">Odeslat</button>
        </form>
    </section>
</div>
<script src="{{ asset('js/chat.js') }}?v=12" defer></script>
@endif
<script src="{{ asset('js/web.js') }}?v=3"></script>
</body>
</html>
