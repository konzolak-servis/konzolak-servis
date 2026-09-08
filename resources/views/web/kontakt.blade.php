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

            @if(session('odeslano'))
                <div class="msg msg--ok">Děkuji, zpráva odešla. Ozvu se co nejdřív.</div>
            @endif
            @if(session('chyba'))
                <div class="msg msg--err">{{ session('chyba') }}</div>
            @endif
            @if($errors->any())
                <div class="msg msg--err">
                    @foreach($errors->all() as $e){{ $e }}<br>@endforeach
                </div>
            @endif

            <form class="form" method="POST" action="{{ route('web.poptavka') }}" style="margin-top:1rem">
                @csrf
                <div class="hp"><label>Nevyplňujte<input type="text" name="web" tabindex="-1" autocomplete="off"></label></div>
                <div>
                    <label for="jmeno">Jméno a příjmení *</label>
                    <input id="jmeno" name="jmeno" required value="{{ old('jmeno') }}">
                </div>
                <div>
                    <label for="email">E-mail *</label>
                    <input id="email" type="email" name="email" required value="{{ old('email') }}">
                </div>
                <div>
                    <label for="telefon">Telefon</label>
                    <input id="telefon" name="telefon" value="{{ old('telefon') }}">
                </div>
                <div>
                    <label for="platforma">Zařízení</label>
                    <select id="platforma" name="platforma">
                        <option value="">— vyberte —</option>
                        @foreach(\App\Support\Platformy::volby() as $skupina => $volby)
                            <optgroup label="{{ $skupina }}">
                                @foreach($volby as $k => $v)
                                    <option value="{{ $k }}" @selected(old('platforma') === $k)>{{ $v }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="zprava">Popis závady *</label>
                    <textarea id="zprava" name="zprava" required>{{ old('zprava') }}</textarea>
                </div>
                <button class="btn btn--primary" type="submit">Odeslat</button>
                <p class="note">Odesláním berete na vědomí zpracování osobních údajů za účelem vyřízení
                    poptávky. Více v <a href="{{ route('web.pravni', 'ochrana-osobnich-udaju') }}">zásadách ochrany osobních údajů</a>.</p>
            </form>
        </div>
    </div>
</section>
@endsection
