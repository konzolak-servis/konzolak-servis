@php
    use App\Support\Web;
    $F = $firma ?? Web::firma();
    $tel = Web::telMezinarodne();
    $adminUrl = rtrim(config('app.url'), '/') . '/admin';
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
    <link rel="icon" type="image/png" sizes="512x512" href="/images/konzolak-icon.png">
    <link rel="apple-touch-icon" href="/images/konzolak-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('css/web.css') }}?v=21">
</head>
<body>
<header class="hdr">
    <div class="wrap hdr__in">
        <a href="{{ route('web.home') }}" class="hdr__logo">
            <img src="/images/konzolak-logo-print.png" alt="{{ $F->nazev ?? 'Konzolák Zlín' }}">
        </a>
        <button class="navtoggle" aria-label="Menu" aria-expanded="false">☰</button>
        <nav class="nav" id="nav">
            @php $r = request()->route()?->getName(); @endphp
            <a href="{{ route('web.opravy') }}" @if(str_starts_with($r ?? '','web.opravy')) aria-current="page" @endif>Opravujeme</a>
            <a href="{{ route('web.cenik') }}" @if($r==='web.cenik') aria-current="page" @endif>Ceník</a>
            <a href="{{ route('web.jak-to-funguje') }}" @if($r==='web.jak-to-funguje') aria-current="page" @endif>Jak to funguje</a>
            <a href="{{ route('web.kontrola') }}" @if(str_starts_with($r ?? '','web.kontrola')) aria-current="page" @endif>Kontrola zakázky</a>
            <a href="{{ route('web.reference') }}" @if($r==='web.reference') aria-current="page" @endif>Reference</a>
            <a href="{{ route('web.faq') }}" @if($r==='web.faq') aria-current="page" @endif>Časté dotazy</a>
            <a href="{{ route('web.kontakt') }}" @if($r==='web.kontakt') aria-current="page" @endif>Kontakt</a>
        </nav>
        <div class="hdr__cta">
            @if($tel)<a class="hdr__tel" href="tel:+{{ $tel }}">{{ $F->telefon }}</a>@endif
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
            </p>
        </div>
        <div>
            <h4>Web</h4>
            <a href="{{ route('web.opravy') }}">Opravujeme</a>
            <a href="{{ route('web.cenik') }}">Ceník</a>
            <a href="{{ route('web.jak-to-funguje') }}">Jak to funguje</a>
            <a href="{{ route('web.kontrola') }}">Kontrola zakázky</a>
            <a href="{{ route('web.kontakt') }}">Kontakt</a>
        </div>
        <div>
            <h4>Informace</h4>
            <a href="{{ route('web.pravni', 'ochrana-osobnich-udaju') }}">Ochrana osobních údajů</a>
            <a href="{{ route('web.pravni', 'reklamacni-rad') }}">Reklamační řád</a>
            <a href="{{ route('web.pravni', 'cookies') }}">Cookies</a>
            <a href="{{ $adminUrl }}">Přihlášení do systému</a>
        </div>
    </div>
    <div class="wrap ftr__legal">
        Provozovatel: {{ $F->nazev ?? 'Konzolák Zlín' }}{{ $F->ico ? ', IČO ' . $F->ico : '' }}{{ ($F->platce_dph ?? false) ? '' : ', neplátce DPH' }}.
        {{ $F->ulice ? $F->ulice . ', ' . trim(($F->psc ?? '') . ' ' . ($F->mesto ?? '')) . '.' : '' }}
        Podnikatel zapsaný v živnostenském rejstříku. Dozorový orgán: Česká obchodní inspekce (coi.gov.cz).
    </div>
</footer>

<script src="{{ asset('js/web.js') }}?v=2"></script>
</body>
</html>
