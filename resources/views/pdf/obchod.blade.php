<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@php
    $jakoProdej = ($jakoProdej ?? false) || $o->typ === 'prodej';
    $jeVykup = ! $jakoProdej;
    $naDalku = $jeVykup && $o->jeVykupNaDalku();
    $castka = $jakoProdej ? (float) ($o->prodejni_cena ?? $o->cena) : (float) $o->cena;
    $datumDokladu = $jakoProdej ? ($o->prodej_datum ?: $o->datum) : $o->datum;
    $hotove = $o->zpusob_uhrady !== 'ucet';
    $zaruka = (int) ($o->prodej_zaruka_mesice ?: 12);
@endphp
@include('pdf.partials.hlavicka', [
    'nadpis' => $jeVykup ? 'Doklad o výkupu' : 'Doklad o prodeji',
    'cislo' => $o->cislo,
])

<table class="cols" style="margin-bottom:3mm">
    <tr>
        <td width="50%">
            <div class="label">{{ $jeVykup ? 'Kupující (vykupující)' : 'Prodávající' }}</div>
            <div class="val"><strong>{{ $firma->nazev }}</strong></div>
            <div>{{ trim(($firma->ulice ? $firma->ulice . ', ' : '') . trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? ''))) }}</div>
            @if ($firma->ico)<div>IČO: {{ $firma->ico }}</div>@endif
        </td>
        <td width="50%">
            <div class="label">{{ $jeVykup ? 'Prodávající' : 'Kupující' }}</div>
            @if ($jeVykup)
                <div class="val"><strong>{{ $o->protistrana_jmeno ?: '……………………………………' }}</strong></div>
                @if ($o->protistrana_kontakt)<div>{{ $o->protistrana_kontakt }}</div>@endif
                @if ($naDalku)
                    @if ($o->protistrana_adresa)<div class="muted">{{ $o->protistrana_adresa }}</div>@endif
                    @if ($o->protistrana_ucet)<div class="muted">Účet: {{ $o->protistrana_ucet }}</div>@endif
                @else
                    <div class="muted">Adresa: {{ $o->protistrana_adresa ?: '……………………………………………' }}</div>
                    <div class="muted">Datum narození: {{ $o->protistrana_narozeni?->format('d.m.Y') ?: '……………………' }}</div>
                    <div class="muted">Číslo OP: {{ $o->protistrana_doklad ?: '……………………' }}</div>
                @endif
            @else
                <div class="val"><strong>{{ $o->prodej_komu ?: 'spotřebitel' }}</strong></div>
                @if ($o->prodej_kontakt)<div>{{ $o->prodej_kontakt }}</div>@endif
            @endif
        </td>
    </tr>
</table>

<table class="items">
    <tr><th>Datum</th><th>Kategorie</th><th>Označení</th><th>Sériové číslo</th><th class="num">Cena</th></tr>
    <tr>
        <td>{{ optional($datumDokladu)->format('d. m. Y') }}</td>
        <td>{{ \App\Models\Obchod::KATEGORIE[$o->kategorie] ?? $o->kategorie }}</td>
        <td>{{ $o->nazev }}</td>
        <td>{{ $o->seriove_cislo ?: '—' }}</td>
        <td class="num">{{ number_format($castka, 0, ',', ' ') }} Kč</td>
    </tr>
</table>

@if ($o->stav_popis)
    <div class="sekce">Stav a příslušenství</div>
    <div class="box">{!! nl2br(e($o->stav_popis)) !!}</div>
@endif

<table class="totalbar">
    <tr>
        <td class="tlabel">{{ $jeVykup ? ($hotove ? 'Vyplaceno v hotovosti' : 'Uhrazeno převodem na účet prodávajícího') : 'Kupní cena' }}</td>
        <td class="tsum">{{ number_format($castka, 0, ',', ' ') }} Kč</td>
    </tr>
</table>

@if ($jakoProdej && ! empty($qr))
    <table width="100%" style="margin-top:3mm"><tr>
        <td width="34mm"><img src="{{ $qr }}" style="width:30mm;height:30mm"></td>
        <td style="vertical-align:middle;font-size:8.5pt">
            <b>QR platba</b> – naskenujte v bankovní aplikaci.<br>
            Účet {{ $firma->cislo_uctu }}, VS {{ preg_replace('/\D/', '', $o->cislo) }}, částka {{ number_format($castka, 2, ',', ' ') }} Kč
        </td>
    </tr></table>
@endif

@if ($naDalku)
    <div class="sekce">Nákup na dálku</div>
    <div class="box" style="font-size:8.5pt">
        Zboží bylo zakoupeno na dálku{{ $o->inzerat_platforma ? ' přes ' . $o->inzerat_platforma : '' }}
        @if ($o->inzerat_url)<br>Inzerát / profil prodávajícího: {{ \Illuminate\Support\Str::limit($o->inzerat_url, 110) }}@endif
        @if ($o->protistrana_ucet)<br>Kupní cena uhrazena bezhotovostně na účet prodávajícího {{ $o->protistrana_ucet }}.@endif
        @if (count($o->dukazy ?? []))<br>Doloženo {{ count($o->dukazy) }} {{ count($o->dukazy) === 1 ? 'přílohou' : 'přílohami' }} (inzerát, komunikace s prodávajícím, potvrzení platby) uloženými u kupujícího.@endif
    </div>
@endif

<div class="pravni">
    @if ($jeVykup && ! $naDalku)
        Prodávající prohlašuje, že je výlučným vlastníkem výše uvedeného zboží, že zboží nepochází z trestné činnosti,
        není zatíženo právy třetích osob a že je oprávněn s ním volně nakládat. Prodávající svým podpisem potvrzuje
        převzetí kupní ceny{{ $hotove ? ' v hotovosti' : '' }}. Kupující ověřil totožnost prodávajícího podle dokladu totožnosti.
    @elseif ($naDalku)
        Prodávající v inzerátu a komunikaci uvedl, že je vlastníkem zboží a je oprávněn je prodat. Doklad slouží jako
        evidence nákupu a původu zboží; podpis prodávajícího se u nákupu na dálku nevyžaduje.
    @else
        Prodává se použité zboží ve stavu uvedeném výše; kupující byl se stavem seznámen.
        Odpovědnost prodávajícího za vady činí {{ $zaruka }} měsíců od převzetí
        @if ($zaruka < 24)(u použitého zboží zkrácena dohodou stran, což občanský zákoník umožňuje)@endif;
        nevztahuje se na opotřebení odpovídající míře používání, kterou mělo zboží při převzetí.
        Vady uplatněte u prodávajícího{{ $firma->telefon ? ', tel. ' . $firma->telefon : '' }}{{ $firma->email ? ', ' . $firma->email : '' }}.
        @if ($o->prodej_na_dalku)
            <br><br><b>Poučení o odstoupení od smlouvy:</b> Smlouva byla uzavřena na dálku. Kupující (spotřebitel) má právo
            od smlouvy odstoupit bez udání důvodu do 14 dnů od převzetí zboží. Odstoupení oznamte prodávajícímu
            písemně{{ $firma->email ? ' (např. e-mailem na ' . $firma->email . ')' : '' }} a zboží vraťte nejpozději do 14 dnů od odstoupení;
            náklady na vrácení nese kupující. Kupní cenu vrátí prodávající do 14 dnů od odstoupení, nejdříve však po vrácení zboží.
            Za snížení hodnoty zboží v důsledku nakládání nad rámec nutného seznámení se zbožím odpovídá kupující.
        @endif
    @endif
</div>

@include('pdf.partials.paticka', array_filter([
    'rucniPodpis' => true,
    'podpisL' => $jeVykup ? 'Za kupujícího (podpis)' : 'Za prodávajícího (podpis)',
    'podpisR' => ($naDalku || (! $jeVykup && $o->prodej_na_dalku)) ? null : ($jeVykup ? 'Prodávající (podpis)' : 'Kupující (podpis)'),
    'doklad' => ($jeVykup ? 'Doklad o výkupu ' : 'Doklad o prodeji ') . $o->cislo,
    'gdpr' => $jeVykup,
], fn ($v) => $v !== null))

@include('pdf.partials.konec')
</body>
</html>
