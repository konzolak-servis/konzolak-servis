<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@php
    $doplatek = $z->doplatek();
    $vraceno = $z->vracenaKauce();
    $prodleni = $z->dnyProdleni();
    $rozpis = (float) $z->poplatek > 0 ? $z->rozpisPoplatku() : [];
    $uprava = round((float) $z->poplatek - collect($rozpis)->sum('castka'), 2);
    $srazky = $z->rozpisSrazek();
    $forma = $z->vyrovnani_forma === 'převodem' ? 'převodem na účet' : 'v hotovosti';
    $zk = $z->zakaznik;
@endphp
@include('pdf.partials.hlavicka', ['nadpis' => $doplatek > 0 ? 'Vyúčtování kauce – doplatek' : 'Vyúčtování a vrácení kauce', 'cislo' => $z->cislo])

<table class="cols" style="margin-bottom:2mm">
    <tr>
        <td width="40%">
            <div class="label">Vypůjčitel</div>
            <div class="val"><strong>{{ $zk?->nazev }}</strong></div>
            @if ($zk?->telefon)<div>tel.: {{ $zk->telefon }}</div>@endif
            @if ($zk?->adresa_radek)<div class="muted">{{ $zk->adresa_radek }}</div>@endif
        </td>
        <td width="33%">
            <div class="label">Zápůjčka</div>
            <div class="val">{{ $z->datum_zapujceni?->format('d.m.Y') }} – {{ ($z->datum_vraceni ?? now())->format('d.m.Y') }}</div>
            <div class="muted">{{ $z->pocetDni() }} {{ \App\Models\Zapujcka::dnu($z->pocetDni()) }}@if ($prodleni > 0), z toho prodlení {{ $prodleni }} {{ \App\Models\Zapujcka::dnu($prodleni) }}@endif</div>
        </td>
        <td width="27%" style="padding-left:3mm">
            <div class="label">Zařízení</div>
            <div class="val" style="font-size:9pt">{{ $z->polozky->pluck('nazev')->implode(', ') }}</div>
        </td>
    </tr>
</table>

<div class="sekce">Vyúčtování</div>
<table class="totalbar">
    <tr>
        <td class="tlabel">Přijatá vratná kauce ({{ $z->datum_zapujceni?->format('d.m.Y') }})</td>
        <td class="tsum" width="45mm" style="border-top:.8px solid #d9dee5">{{ number_format((float) $z->kauce, 2, ',', ' ') }} Kč</td>
    </tr>

    {{-- nájemné --}}
    @if ($z->zdarma)
        <tr>
            <td class="tlabel">Nájemné – zdarma po dobu opravy{{ $z->zakazka ? ' (' . $z->zakazka->cislo . ')' : '' }}</td>
            <td class="tsum" width="45mm" style="border-top:.8px solid #d9dee5">0,00 Kč</td>
        </tr>
    @endif
    @foreach ($rozpis as $r)
        <tr>
            <td class="tlabel">Nájemné {{ $r['popis'] }} à {{ number_format($r['sazba'], 0, ',', ' ') }} Kč</td>
            <td class="tsum" width="45mm" style="border-top:.8px solid #d9dee5">− {{ number_format($r['castka'], 2, ',', ' ') }} Kč</td>
        </tr>
    @endforeach
    @if (abs($uprava) >= 0.01)
        <tr>
            <td class="tlabel">{{ $uprava < 0 ? 'Sleva z nájemného' : 'Úprava nájemného' }}</td>
            <td class="tsum" width="45mm" style="border-top:.8px solid #d9dee5">{{ $uprava < 0 ? '+' : '−' }} {{ number_format(abs($uprava), 2, ',', ' ') }} Kč</td>
        </tr>
    @endif

    {{-- srážky --}}
    @foreach ($srazky as $s)
        <tr>
            <td class="tlabel">{{ $s['druh'] }}{{ $s['popis'] ? ' – ' . $s['popis'] : '' }}</td>
            <td class="tsum" width="45mm" style="border-top:.8px solid #d9dee5">− {{ number_format($s['castka'], 2, ',', ' ') }} Kč</td>
        </tr>
    @endforeach

    <tr>
        <td class="tlabel"><b>{{ $doplatek > 0 ? 'Vypůjčitel doplácí' : 'Vráceno vypůjčiteli' }}</b></td>
        <td class="tsum" width="45mm">{{ number_format($doplatek > 0 ? $doplatek : $vraceno, 2, ',', ' ') }} Kč</td>
    </tr>
</table>

<table class="cols" style="margin-top:3mm">
    <tr>
        <td width="50%"><div class="label">Forma {{ $doplatek > 0 ? 'úhrady doplatku' : 'vrácení' }}</div><div class="val">{{ $forma }}</div></td>
        <td width="50%"><div class="label">Datum</div><div class="val">{{ ($z->datum_vraceni ?? now())->format('d.m.Y') }}</div></td>
    </tr>
</table>

<div class="upozorneni">
    @if ($doplatek > 0)
        Nájemné a náhrada škody převyšují přijatou kauci. Kauce byla započtena v plné výši a vypůjčitel
        uhradil doplatek <b>{{ number_format($doplatek, 0, ',', ' ') }} Kč</b> {{ $forma }}.
        Podpisem vypůjčitel potvrzuje souhlas s vyúčtováním a servis přijetí doplatku.
    @else
        Vypůjčitel podpisem potvrzuje převzetí vrácené kauce ve výši <b>{{ number_format($vraceno, 0, ',', ' ') }} Kč</b>
        {{ $forma }}{{ (float) $z->poplatek + (float) $z->kauce_srazka > 0 ? ' a souhlas s vyúčtováním nájemného a srážek' : '' }}.
    @endif
    Tímto jsou vzájemné nároky ze zápůjčky {{ $z->cislo }} vypořádány. Vyhotoveno ve dvou stejnopisech.
</div>

@include('pdf.partials.paticka', [
    'rucniPodpis' => true,
    'podpisL' => $doplatek > 0 ? 'Doplatek přijal za servis' : 'Kauci vyplatil za servis',
    'podpisR' => $doplatek > 0 ? 'Doplatek uhradil vypůjčitel (podpis)' : 'Kauci převzal vypůjčitel (podpis)',
    'doklad' => 'Vyúčtování kauce ' . $z->cislo,
])

@include('pdf.partials.konec')
</body>
</html>
