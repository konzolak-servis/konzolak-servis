@extends('web.layout')
@section('title', 'Jak to funguje – příjem oprav | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Jak probíhá oprava: od popisu závady přes diagnostiku a odsouhlasení ceny až po vrácení zařízení.')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Jak to funguje</h1>
        <p>Od popisu závady po vrácení opraveného zařízení. Cenu vždy odsouhlasíte předem.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        @include('web.partials.kroky')
    </div>
</section>

<section class="section section--tint">
    <div class="wrap grid grid--2">
        <div>
            <h2>Osobní předání ve Zlíně</h2>
            @include('web.partials.mista')
            <p style="margin-top:.8rem;color:#5b6472">Volejte prosím vždy předem – ať mám zařízení
                připravené a jsem na místě.</p>
        </div>
        <div>
            <h2>Zaslání poštou</h2>
            <ul class="prose">
                <li>Zařízení zabalte do dostatečné výplně (bublinková fólie, polystyren).</li>
                <li>Přiložte lístek s popisem závady a kontaktem (telefon, e-mail).</li>
                <li>Pošlete Zásilkovnou nebo poštou na adresu
                    @if($firma->ulice)<strong>{{ $firma->nazev }}, {{ $firma->ulice }}, {{ trim(($firma->psc ?? '').' '.($firma->mesto ?? '')) }}</strong>@endif.</li>
                <li>Po přijetí se ozvu s výsledkem diagnostiky a cenou.</li>
            </ul>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap prose">
        <h2>Cena, diagnostika a odsouhlasení</h2>
        <p>Uvedené ceny jsou orientační a během servisního zásahu se mohou upřesnit.
            Přesnou cenu sdělím po diagnostice a opravu provedu až po vašem odsouhlasení
            (e-mailem nebo telefonicky). Provedu-li opravu, diagnostiku neúčtuji.</p>

        <h2>Odpovědnost za vady provedené opravy</h2>
        <p>Je-li objednatel spotřebitel, může práva z vadného plnění uplatnit v zákonné lhůtě
            24 měsíců od převzetí opravené věci; u oprav použitých nebo starších zařízení činí
            tato lhůta 12 měsíců. Je-li objednatel podnikatel, činí lhůta pro uplatnění vad
            3 měsíce od převzetí. Reklamace se vyřizuje do 30 dnů. Podrobnosti najdete
            v <a href="{{ route('web.pravni', 'reklamacni-rad') }}">reklamačním řádu</a>.</p>

        <h2>Data v zařízení</h2>
        <p>Snažím se data zachovat, ale za data uložená na discích a paměťových kartách nemohu
            ručit. Před předáním si prosím vše zálohujte. Opravené zařízení vydávám proti
            předložení originálu dokladu o převzetí.</p>
    </div>
</section>
@endsection
