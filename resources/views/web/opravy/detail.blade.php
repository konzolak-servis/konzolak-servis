@extends('web.layout')
@section('title', $s['nadpis'] . ' | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', $s['perex'])

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>{{ $s['nadpis'] }}</h1>
        <p>{{ $s['perex'] }}</p>
        <div class="hero__cta">
            <a class="btn btn--primary" href="{{ route('web.kontakt') }}#poptavka">Objednat opravu</a>
            <a class="btn btn--ghost" href="#cenik">Ceník</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <h2>Modely, které opravuji</h2>
        <div class="chips">
            @foreach($s['modely'] as $m)<span class="chip">{{ $m }}</span>@endforeach
        </div>

        <h2 style="margin-top:2rem">Nejčastější opravy</h2>
        <ul class="oprava-list">
            @foreach($s['opravy'] as $o)<li>{{ $o }}</li>@endforeach
        </ul>
    </div>
</section>

<section class="section section--tint" id="cenik">
    <div class="wrap">
        <h2>Orientační ceník</h2>
        @if($cenik->count())
            <table class="cenik-tab" style="max-width:640px;margin-top:1rem">
                @foreach($cenik as $p)
                    <tr>
                        <td>{{ $p->nazev }}</td>
                        <td class="cena">{{ $p->cena > 0 ? number_format($p->cena, 0, ',', ' ') . ' Kč' : 'dle diagnostiky' }}</td>
                    </tr>
                @endforeach
            </table>
            <p class="cenik-pozn">Ceny jsou orientační. Přesnou cenu řeknu po diagnostice a opravuji až po vašem odsouhlasení.</p>
        @else
            <p class="lead">Ceník pro tuto kategorii zatím připravuji – napište mi a cenu spočítám individuálně.</p>
        @endif
        <p style="margin-top:1rem"><a class="btn btn--dark btn--sm" href="{{ route('web.cenik') }}">Celý ceník</a></p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <h2>Jak opravu objednat</h2>
        <div style="margin-top:1.2rem">@include('web.partials.kroky', ['kroky' => \App\Support\Web::kroky()])</div>
    </div>
</section>
@endsection
