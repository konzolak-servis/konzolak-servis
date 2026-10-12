@extends('web.layout')
@section('title', 'Kontakt a objednání opravy | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Kontakt na servis Konzolák Zlín – telefon, e-mail, adresa a formulář pro popis závady.')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Kontakt</h1>
        <p>Nejrychleji telefonicky nebo na WhatsApp. Zařízení předávejte vždy po domluvě.</p>
    </div>
</section>

<section class="section">
    <div class="wrap grid grid--2">
        <div>
            <h2>Spojení</h2>
            <p style="font-size:1.05rem">
                @if($firma->telefon)
                    <strong>Telefon / WhatsApp:</strong>
                    <a href="tel:+{{ \App\Support\Web::telMezinarodne() }}">{{ $firma->telefon }}</a><br>
                @endif
                @if($firma->email)
                    <strong>E-mail:</strong> <a href="mailto:{{ $firma->email }}">{{ $firma->email }}</a><br>
                @endif
                @if($firma->ico)<strong>IČO:</strong> {{ $firma->ico }}@if(!($firma->platce_dph ?? false)) · neplátce DPH @endif<br>@endif
            </p>

            <h3 style="margin-top:1.5rem">Kde mě zastihnete</h3>
            @include('web.partials.mista')

            @if($firma->ulice)
                <p style="margin-top:1rem;color:#5b6472">Zásilky posílejte na:
                    <strong>{{ $firma->nazev }}, {{ $firma->ulice }}, {{ trim(($firma->psc ?? '').' '.($firma->mesto ?? '')) }}</strong></p>
            @endif
        </div>

        <div id="poptavka">
            <h2>Popište závadu</h2>

            @include('web.partials.poptavka-form', ['typ' => 'oprava'])
        </div>
    </div>
</section>
@endsection
