@extends('web.layout')
@section('title', 'Zaslání zařízení do servisu | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Jak poslat konzoli, ovladač nebo počítač do servisu poštou či Zásilkovnou: domluva, balení, adresa a vrácení opravy.')

@section('body')
@php $K = \App\Support\Web::kontakty(); @endphp
<section class="hero">
    <div class="wrap hero__in">
        <h1>Zaslání zařízení</h1>
        <p>Nemusíte jezdit do Zlína. Zařízení pošlete a po opravě vám ho zabalené vrátím. Před odesláním se mi prosím ozvěte.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="kroky" style="grid-template-columns:repeat(auto-fit,minmax(230px,1fr))">
            <div class="kroky__i"><div class="kroky__n">1</div><h3>Nejdřív se ozvěte</h3>
                <p>Zavolejte, napište na WhatsApp nebo e-mail. Popište závadu, domluvíme způsob odeslání a předběžnou cenu.</p></div>
            <div class="kroky__i"><div class="kroky__n">2</div><h3>Zabalte</h3>
                <p>Do pevné krabice s výplní. Ke konzoli stačí napájecí kabel; ovladače posílejte bez zbytečného příslušenství.</p></div>
            <div class="kroky__i"><div class="kroky__n">3</div><h3>Přibalte lístek</h3>
                <p>Jméno, telefon, e-mail, adresu pro vrácení a stručný popis závady. Příp. číslo domluvené zakázky.</p></div>
            <div class="kroky__i"><div class="kroky__n">4</div><h3>Odešlete</h3>
                <p>Poštou, Zásilkovnou nebo jiným přepravcem. Doporučuji zásilku pojistit a uschovat si podací lístek.</p></div>
        </div>
    </div>
</section>

<section class="section section--tint">
    <div class="wrap grid grid--2">
        <div class="card">
            <h3>Adresa pro zaslání</h3>
            <p style="font-size:1.1rem;color:#fff;margin-bottom:.4rem">
                <strong>{{ $firma->nazev }}</strong><br>
                {{ $firma->ulice }}<br>
                {{ trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? '')) }}
            </p>
            <p>Telefon: @if($K['tel'])<a href="{{ $K['tel'] }}">{{ $firma->telefon }}</a>@endif
                @if($firma->email)<br>E-mail: <a href="mailto:{{ $firma->email }}">{{ $firma->email }}</a>@endif</p>
            <p>Zásilkovnu nebo Balíkovnu rád domluvím – napište a pošlu údaje výdejního místa.</p>
        </div>
        <div class="card">
            <h3>Po opravě</h3>
            <ul class="prose">
                <li>Zařízení po zaslání zkontroluji, ozvu se s výsledkem diagnostiky a cenou.</li>
                <li>Opravuji až po vašem odsouhlasení. Objeví-li se jiná závada, nejdřív se zeptám.</li>
                <li>Stav zakázky můžete sledovat online přes <a href="{{ route('web.kontrola') }}">Kontrolu zakázky</a>.</li>
                <li>Zpět posílám po úhradě (převodem s QR platbou), nebo po domluvě na dobírku.</li>
                <li>K opravě dostanete servisní protokol se záznamem provedené práce.</li>
            </ul>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="cta-box">
            <div>
                <h2>Nejste si jistí závadou?</h2>
                <p class="lead">Napište, co zařízení dělá nebo nedělá. Poradím další postup a orientační cenu.</p>
            </div>
            <div class="cta-box__btns">
                @if($K['tel'])<a class="btn btn--primary" href="{{ $K['tel'] }}">Zavolat</a>@endif
                @if($K['whatsapp'])<a class="btn btn--ghost" href="{{ $K['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp</a>@endif
                <a class="btn btn--dark" href="{{ route('web.kontakt') }}#poptavka">Formulář</a>
            </div>
        </div>
    </div>
</section>
@endsection
