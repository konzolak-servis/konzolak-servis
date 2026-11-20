<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@include('pdf.partials.hlavicka', ['nadpis' => 'Protokol o vrácení zařízení', 'cislo' => $z->cislo])

@include('pdf.partials.zapujcka-hlava')

@php
    $vraceno = $z->datum_vraceni ?? now();
    $prodleni = $z->dnyProdleni();
    $srazky = $z->rozpisSrazek();
@endphp

<table class="cols" style="margin:1mm 0 2mm">
    <tr>
        <td width="33%"><div class="label">Vráceno dne</div><div class="val"><strong>{{ $vraceno->format('d.m.Y') }}</strong></div></td>
        <td width="33%"><div class="label">Doba zápůjčky</div><div class="val">{{ $z->pocetDni() }} {{ \App\Models\Zapujcka::dnu($z->pocetDni()) }}</div></td>
        <td width="34%"><div class="label">Vráceno v termínu</div>
            <div class="val">{!! $prodleni > 0 ? '<b style="color:#b91c1c">ne – prodlení ' . $prodleni . ' ' . \App\Models\Zapujcka::dnu($prodleni) . '</b>' : 'ano' !!}</div></td>
    </tr>
</table>

<div class="sekce">Vrácená zařízení</div>
<table class="items">
    <tr><th>#</th><th>Zařízení</th><th>Sériové číslo</th><th>Stav při předání</th><th>Stav při vrácení</th></tr>
    @foreach ($z->polozky as $p)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td><b>{{ $p->nazev }}</b>
                @if ($p->prislusenstvi)<div class="muted" style="font-size:8pt">{{ $p->prislusenstvi }}</div>@endif
            </td>
            <td>{{ $p->seriove_cislo ?: '—' }}</td>
            <td>{{ $p->stav_pri_zapujceni ?: 'funkční' }}</td>
            <td>{{ $p->stav_pri_vraceni ?: '—' }}</td>
        </tr>
    @endforeach
</table>

<div class="sekce">Zjištěné vady a nedostatky</div>
@if (count($srazky))
    <table class="items">
        <tr><th>Druh</th><th>Popis</th></tr>
        @foreach ($srazky as $s)
            <tr><td width="35%"><b>{{ $s['druh'] }}</b></td><td>{{ $s['popis'] ?: '—' }}</td></tr>
        @endforeach
    </table>
    <div class="muted" style="font-size:8pt">Vyčíslení náhrady je uvedeno v dokladu o vyúčtování kauce {{ $z->cislo }}.</div>
@else
    <div class="box">Bez vad – zařízení vrácena kompletní a v pořádku.</div>
@endif

@if ($z->poznamka_vraceni)
    <div class="sekce">Poznámka</div>
    <div class="box">{!! nl2br(e($z->poznamka_vraceni)) !!}</div>
@endif

<div class="upozorneni">
    Servis potvrzuje převzetí výše uvedených zařízení v uvedeném stavu. Vypůjčitel podpisem potvrzuje předání
    zařízení a souhlas se zjištěným stavem{{ count($srazky) ? ' a uvedenými vadami' : '' }}.
    Vyhotoveno ve dvou stejnopisech, každá strana obdrží jeden.
</div>

@include('pdf.partials.paticka', [
    'podpisL' => 'Zařízení převzal za servis',
    'podpisR' => 'Zařízení předal vypůjčitel (podpis)',
    'doklad' => 'Protokol o vrácení zařízení ' . $z->cislo,
])

@include('pdf.partials.konec')
</body>
</html>
