<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@include('pdf.partials.hlavicka', ['nadpis' => 'Doklad o prodeji', 'cislo' => $p->cislo])

@php($zk = $p->zakaznik)
<table class="cols" style="margin-bottom:3mm">
    <tr>
        <td width="50%">
            <div class="label">Prodávající</div>
            <div class="val"><strong>{{ $firma->nazev }}</strong></div>
            <div>{{ trim(($firma->ulice ? $firma->ulice . ', ' : '') . trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? ''))) }}</div>
            @if ($firma->ico)<div>IČO: {{ $firma->ico }}</div>@endif
        </td>
        <td width="30%">
            <div class="label">Kupující</div>
            <div class="val"><strong>{{ $p->kupujiciNazev() }}</strong></div>
            @if ($zk?->telefon)<div>{{ $zk->telefon }}</div>@endif
            @if ($zk?->adresa_radek)<div class="muted">{{ $zk->adresa_radek }}</div>@endif
            @if ($zk?->ico)<div class="muted">IČO: {{ $zk->ico }}</div>@endif
        </td>
        <td width="20%">
            <div class="label">Datum prodeje</div>
            <div class="val">{{ $p->datum?->format('d.m.Y') }}</div>
        </td>
    </tr>
</table>

<table class="items">
    <tr><th>#</th><th>Zboží</th><th class="num">Ks</th><th class="num">Cena / ks</th><th class="num">Celkem</th></tr>
    @foreach ($p->polozky as $i)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $i->nazev }}</td>
            <td class="num">{{ rtrim(rtrim(number_format((float) $i->mnozstvi, 3, ',', ' '), '0'), ',') }}</td>
            <td class="num">{{ number_format((float) $i->cena_ks, 2, ',', ' ') }} Kč</td>
            <td class="num">{{ number_format((float) $i->celkem, 2, ',', ' ') }} Kč</td>
        </tr>
    @endforeach
</table>

<table class="totalbar">
    <tr>
        <td class="tlabel">{{ match ($p->platba) { 'hotove' => 'Zaplaceno v hotovosti', 'qr_na_miste' => 'Zaplaceno převodem (QR)', default => 'K úhradě převodem' } }}{{ $firma->platce_dph ? '' : ' · neplátce DPH' }}</td>
        <td class="tsum" width="45mm">{{ number_format((float) $p->celkem, 2, ',', ' ') }} Kč</td>
    </tr>
</table>

@if ($p->platba === 'ucet' && $qr)
    <table width="100%" style="margin-top:3mm"><tr>
        <td width="34mm"><img src="{{ $qr }}" style="width:30mm;height:30mm"></td>
        <td style="vertical-align:middle;font-size:8.5pt">
            <b>QR platba</b> – naskenujte v bankovní aplikaci.<br>
            Účet {{ $firma->cislo_uctu }}, VS {{ preg_replace('/\D/', '', $p->cislo) }}, částka {{ number_format((float) $p->celkem, 2, ',', ' ') }} Kč
        </td>
    </tr></table>
@endif

@if ($p->poznamka)
    <div class="sekce">Poznámka</div>
    <div class="box">{!! nl2br(e($p->poznamka)) !!}</div>
@endif

<div class="pravni">
    Odpovědnost prodávajícího za vady činí {{ (int) $p->zaruka_mesice }} měsíců od převzetí{{ (int) $p->zaruka_mesice < 24 ? ' (použité zboží – lhůta zkrácena dohodou stran, což občanský zákoník umožňuje)' : '' }}.
    Vady uplatněte u prodávajícího{{ $firma->telefon ? ', tel. ' . $firma->telefon : '' }}{{ $firma->email ? ', ' . $firma->email : '' }}
    a předložte tento doklad. Odpovědnost se nevztahuje na vady způsobené nesprávnou montáží, mechanickým poškozením
    nebo neodborným zásahem kupujícího.
</div>

@include('pdf.partials.paticka', [
    'rucniPodpis' => true,
    'podpisL' => 'Za prodávajícího (podpis)',
    'podpisR' => 'Kupující (podpis)',
    'doklad' => 'Doklad o prodeji ' . $p->cislo,
])

@include('pdf.partials.konec')
</body>
</html>
