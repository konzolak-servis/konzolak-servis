@extends('web.layout')
@section('title', 'Kontrola stavu zakázky | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Zjistěte aktuální stav své opravy podle čísla zakázky a příjmení.')
@section('robots', 'noindex,follow')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Kontrola stavu zakázky</h1>
        <p>Zadejte číslo zakázky a příjmení z dokladu o převzetí – ukážu vám aktuální stav opravy.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        @if(session('chyba'))
            <div class="msg msg--err" style="max-width:560px;margin-bottom:1rem">{{ session('chyba') }}</div>
        @endif
        @if($errors->any())
            <div class="msg msg--err" style="max-width:560px;margin-bottom:1rem">
                @foreach($errors->all() as $e){{ $e }}<br>@endforeach
            </div>
        @endif

        <form class="form" method="POST" action="{{ route('web.kontrola.najdi') }}">
            @csrf
            <div class="hp"><label>Nevyplňujte<input type="text" name="web" tabindex="-1" autocomplete="off"></label></div>
            <div>
                <label for="cislo">Číslo zakázky *</label>
                <input id="cislo" name="cislo" placeholder="např. SL-2026-0042" required value="{{ old('cislo') }}">
            </div>
            <div>
                <label for="overeni">Příjmení, e-mail nebo telefon *</label>
                <input id="overeni" name="overeni" required value="{{ old('overeni') }}"
                       placeholder="pro ověření – cokoli z dokladu">
            </div>
            <button class="btn btn--primary" type="submit">Zobrazit stav</button>
            <p class="note">Obojí najdete na dokladu o převzetí, který jste dostali při předání zařízení.
                Stačí příjmení, e-mail nebo telefonní číslo.</p>
        </form>
    </div>
</section>
@endsection
