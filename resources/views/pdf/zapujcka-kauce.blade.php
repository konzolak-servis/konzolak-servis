<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@include('pdf.partials.hlavicka', ['nadpis' => 'Doklad o přijetí kauce', 'cislo' => $z->cislo])

@php($zk = $z->zakaznik)
<table class="cols" style="margin-bottom:2mm">
    <tr>
        <td width="50%">
            <div class="label">Přijato od</div>
            <div class="val"><strong>{{ $zk?->nazev }}</strong></div>
            @if ($zk?->telefon)<div>tel.: {{ $zk->telefon }}</div>@endif
            @if ($zk?->adresa_radek)<div class="muted">{{ $zk->adresa_radek }}</div>@endif
        </td>
        <td width="25%">
            <div class="label">Datum přijetí</div>
            <div class="val">{{ $z->datum_zapujceni?->format('d.m.Y') }}</div>
        </td>
        <td width="25%">
            <div class="label">Forma</div>
            <div class="val">{{ $z->kauce_forma === 'převodem' ? 'převodem na účet' : 'v hotovosti' }}</div>
        </td>
    </tr>
</table>

<table class="totalbar">
    <tr>
        <td class="tlabel">Přijatá vratná kauce</td>
        <td class="tsum" width="45mm">{{ number_format((float) $z->kauce, 2, ',', ' ') }} Kč</td>
    </tr>
</table>

<div class="sekce">Účel</div>
<div class="box">
    Vratná kauce k zápůjčce <b>{{ $z->cislo }}</b>
    ({{ $z->polozky->pluck('nazev')->implode(', ') }}){{ $z->zakazka ? ', k zakázce ' . $z->zakazka->cislo : '' }}.
</div>

<div class="upozorneni">
    Kauce bude vrácena při vrácení zapůjčených zařízení v pořádku a kompletních.
    Při poškození, ztrátě příslušenství nebo nevrácení zařízení může být z kauce sražena odpovídající částka.
    Kauce není platbou za služby.
</div>

@include('pdf.partials.paticka', [
    'podpisL' => 'Přijal za servis',
    'podpisR' => 'Kauci předal (podpis)',
    'doklad' => 'Doklad o přijetí kauce ' . $z->cislo,
])

@include('pdf.partials.konec')
</body>
</html>
