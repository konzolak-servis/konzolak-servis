@extends('web.layout')
@section('title', ($firma->nazev ?? 'Konzolák Zlín') . ' – servis herních konzolí a PC ve Zlíně')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Servis herních konzolí a <span class="accent">PC ve Zlíně</span></h1>
        <p>Opravím PlayStation, Xbox, Nintendo i počítače. Diagnostika zdarma při opravě,
            cenu vždy odsouhlasíte předem. Osobní přístup jednoho technika.</p>
        <div class="hero__cta">
            <a class="btn btn--primary" href="{{ route('web.kontakt') }}#poptavka">Objednat opravu</a>
            <a class="btn btn--ghost" href="{{ route('web.kontrola') }}">Zkontrolovat stav zakázky</a>
        </div>
        @if($firma->telefon)
            <div class="hero__note">Nejrychleji telefonicky:
                <a href="tel:+{{ \App\Support\Web::telMezinarodne() }}">{{ $firma->telefon }}</a>
                · volejte prosím vždy předem.</div>
        @endif
        <div class="stats">
            <div><b>100 %</b><span>zákazníků doporučuje</span></div>
            <div><b>PS · Xbox</b><span>Nintendo, PC i ovladače</span></div>
            <div><b>Zlín</b><span>osobně i poštou po ČR</span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        @include('web.partials.duvera')
    </div>
</section>

<section class="section section--tint">
    <div class="wrap">
        <h2>Co opravuji</h2>
        <p class="lead">Vyberte zařízení – u každého najdete nejčastější opravy i ceník.</p>
        <div class="grid grid--3" style="margin-top:1.8rem">
            @php $zkr = ['playstation'=>'PS','xbox'=>'XB','nintendo'=>'NIN','pc-a-notebooky'=>'PC','ovladace'=>'OVL']; @endphp
            @foreach($dlazdice as $d)
                <a class="card card--link" href="{{ route('web.opravy.detail', $d['slug']) }}" style="text-decoration:none;color:inherit;display:block">
                    <div class="card__ico">{{ $zkr[$d['slug']] ?? '•' }}</div>
                    <h3>{{ $d['nazev'] }}</h3>
                    <p>{{ $d['perex'] }}</p>
                    <span class="more">Detail a ceník</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <h2>Jak to funguje</h2>
        <div style="margin-top:1.5rem">@include('web.partials.kroky')</div>
    </div>
</section>

@if($ukazkaCeniku->count())
<section class="section section--tint">
    <div class="wrap">
        <h2>Orientační ceny</h2>
        <p class="lead">Přesná cena vždy až po diagnostice a po vašem odsouhlasení.</p>
        <table class="cenik-tab" style="margin-top:1.2rem;max-width:640px">
            @foreach($ukazkaCeniku as $p)
                <tr>
                    <td>{{ $p->nazev }} <span style="color:#8a8578">· {{ \App\Support\Platformy::label($p->kategorie) }}</span></td>
                    <td class="cena">{{ $p->cena > 0 ? number_format($p->cena, 0, ',', ' ') . ' Kč' : 'dle diagnostiky' }}</td>
                </tr>
            @endforeach
        </table>
        <p style="margin-top:1rem"><a class="btn btn--dark btn--sm" href="{{ route('web.cenik') }}">Celý ceník</a></p>
    </div>
</section>
@endif

<section class="section">
    <div class="wrap grid grid--2" style="align-items:center">
        <div>
            <h2>O mně</h2>
            <p>Jsem {{ $firma->nazev ?? 'Konzolák Zlín' }} – servis herních konzolí, notebooků a PC ve Zlíně.
                Dělám to poctivě a osobně: zařízení projde jen mýma rukama, o ceně i postupu se domluvíme předem.</p>
            <p style="color:#8a8578"><em>(Delší text a fotky dílny doplníme.)</em></p>
            <a class="btn btn--dark btn--sm" href="{{ route('web.o-nas') }}">Více o servisu</a>
        </div>
        <div class="card">
            <h3>Kde mě zastihnete</h3>
            @include('web.partials.mista')
        </div>
    </div>
</section>
@endsection
