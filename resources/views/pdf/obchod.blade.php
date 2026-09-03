<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@php($jeVykup = $o->typ === 'vykup')
@include('pdf.partials.hlavicka', [
    'nadpis' => $jeVykup ? 'Doklad o výkupu' : 'Doklad o prodeji',
    'cislo' => $o->cislo,
])

<table class="cols" style="margin-bottom:3mm">
    <tr>
        <td width="50%">
            <div class="label">{{ $jeVykup ? 'Vykupující' : 'Prodávající' }}</div>
            <div class="val"><strong>{{ $firma->nazev }}</strong></div>
            <div>{{ trim(($firma->ulice ? $firma->ulice . ', ' : '') . trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? ''))) }}</div>
            @if ($firma->ico)<div>IČO: {{ $firma->ico }}</div>@endif
        </td>
        <td width="50%">
            <div class="label">{{ $jeVykup ? 'Prodávající' : 'Kupující' }}</div>
            <div class="val"><strong>{{ $o->protistrana_jmeno ?: '—' }}</strong></div>
            @if ($o->protistrana_kontakt)<div>{{ $o->protistrana_kontakt }}</div>@endif
            @if ($jeVykup && $o->protistrana_doklad)<div class="muted">Doklad totožnosti: {{ $o->protistrana_doklad }}</div>@endif
        </td>
    </tr>
</table>

<table class="items">
    <tr><th>Datum</th><th>Kategorie</th><th>Označení</th><th>Sériové číslo</th><th class="num">Cena</th></tr>
    <tr>
        <td>{{ optional($o->datum)->format('d. m. Y') }}</td>
        <td>{{ \App\Models\Obchod::KATEGORIE[$o->kategorie] ?? $o->kategorie }}</td>
        <td>{{ $o->nazev }}</td>
        <td>{{ $o->seriove_cislo ?: '—' }}</td>
        <td class="num">{{ number_format($o->cena, 0, ',', ' ') }} Kč</td>
    </tr>
</table>

@if ($o->stav_popis)
    <div class="sekce">Stav a příslušenství</div>
    <div class="box">{!! nl2br(e($o->stav_popis)) !!}</div>
@endif

<table class="totalbar">
    <tr>
        <td class="tlabel"></td>
        <td class="tsum">
            {{ number_format($o->cena, 0, ',', ' ') }} Kč
            <div style="font-size:6.5pt;font-weight:normal;color:#9ca3af">
                {{ $jeVykup ? 'VYPLACENO' : 'K ÚHRADĚ' }}
            </div>
        </td>
    </tr>
</table>

<div class="pravni">
    @if ($jeVykup)
        Prodávající prohlašuje, že je oprávněným vlastníkem výše uvedeného zboží, že zboží nepochází z trestné činnosti
        a že je oprávněn s ním volně nakládat. Prodávající svým podpisem potvrzuje převzetí kupní ceny v hotovosti.
    @else
        Kupující byl s výrobkem seznámen a přebírá jej ve stavu uvedeném výše. Jde o použité zboží.
        Případná záruka je sjednána individuálně.
    @endif
</div>

@include('pdf.partials.paticka', [
    'podpisL' => $jeVykup ? 'Za výkup (podpis)' : 'Za prodej (podpis)',
    'podpisR' => $jeVykup ? 'Prodávající (podpis)' : 'Kupující (podpis)',
    'doklad' => ($jeVykup ? 'Doklad o výkupu ' : 'Doklad o prodeji ') . $o->cislo,
])

@include('pdf.partials.konec')
</body>
</html>
