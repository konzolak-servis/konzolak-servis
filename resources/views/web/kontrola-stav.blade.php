@extends('web.layout')
@section('title', 'Stav zakázky ' . $z->cislo . ' | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('robots', 'noindex,nofollow')

@section('body')
<div class="wrap stav-wrap">
    <div class="stav-card">
        <div class="stav-eyebrow">Zakázka</div>
        <div class="stav-cislo">{{ $z->cislo }}</div>
        @if($z->zarizeni)
            <div class="stav-zar">{{ $z->zarizeni->oznaceni }}</div>
        @endif

        <div class="stav-hl t-{{ $tonalita }}">
            <h2>{{ $nadpis }}</h2>
            @if($popis)<p>{{ $popis }}</p>@endif
        </div>

        @if(! in_array($z->stav, ['storno', 'nerentabilni'], true))
            <ol class="stav-steps">
                @foreach(['Přijato', 'Diagnostika', 'Oprava', 'Hotovo', 'Vyzvednuto'] as $i => $label)
                    <li class="{{ $i < $krok ? 'done' : ($i === $krok ? 'done cur' : '') }}">{{ $label }}</li>
                @endforeach
            </ol>
        @endif

        <div class="stav-rows">
            <div class="stav-row">
                <span class="k">Přijato</span>
                <span class="v">{{ optional($z->datum_prijeti)->format('d.m.Y') ?: '—' }}</span>
            </div>
            @if($z->datum_vyrizeni)
                <div class="stav-row">
                    <span class="k">Vyřízeno</span>
                    <span class="v">{{ $z->datum_vyrizeni->format('d.m.Y') }}</span>
                </div>
            @endif
            @if(in_array($z->stav, ['hotovo', 'vydano'], true) && $z->cena_celkem > 0)
                <div class="stav-row">
                    <span class="k">{{ $z->stav === 'vydano' ? 'Zaplaceno' : 'K úhradě' }}</span>
                    <span class="v big">{{ number_format($kUhrade, 0, ',', ' ') }} Kč</span>
                </div>
            @elseif($z->predpokladana_cena > 0)
                <div class="stav-row">
                    <span class="k">Předpokládaná cena</span>
                    <span class="v">{{ number_format((float) $z->predpokladana_cena, 0, ',', ' ') }} Kč</span>
                </div>
            @endif
            @if($z->zaruka_mesice && $z->stav === 'vydano')
                <div class="stav-row">
                    <span class="k">Záruka</span>
                    <span class="v">{{ $z->zaruka_mesice }} měs.</span>
                </div>
            @endif
        </div>

        @if($z->stav === 'hotovo')
            <div class="stav-box">
                <h3>Kde a kdy vyzvednout</h3>
                @if($firma->email_vyzvednuti)
                    <div class="adr">{{ trim($firma->email_vyzvednuti) }}</div>
                @elseif($firma->ulice)
                    <div class="adr">{{ $firma->ulice }}, {{ trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? '')) }}</div>
                @endif
                @if($firma->telefon)
                    <p style="margin:.6rem 0 0">Volejte předem:
                        <a href="tel:+{{ \App\Support\Web::telMezinarodne() }}">{{ $firma->telefon }}</a></p>
                @endif
            </div>
        @endif

        <div class="stav-back">
            <a class="btn btn--dark btn--sm" href="{{ route('web.home') }}">← Zpět na web</a>
        </div>
    </div>
</div>
@endsection
