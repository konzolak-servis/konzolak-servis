<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">
<style>
    {{-- štítek 50 × 30 mm (okraje 1,5 mm): vlevo údaje, vpravo QR 23 mm --}}
    * { font-family: dejavusans, sans-serif; }
    body { margin: 0; color: #0F2038; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 0; }
    .cislo { font-size: 10pt; font-weight: bold; }
    .radek { font-size: 5.5pt; line-height: 1.2; margin-top: .4mm; }
    .radek b { color: #9a7420; }
    .qr { width: 23.5mm; text-align: right; vertical-align: top; }
    .qr img { width: 23mm; height: 23mm; }
    .firma { font-size: 5pt; color: #888; text-align: center; }
</style>
</head>
<body>
@php
    $zarizeni = $z->vsechnaZarizeni();
    $prvni = $zarizeni->first();
@endphp
<table>
    <tr>
        <td style="vertical-align:top">
            <div class="cislo">{{ $z->cislo }}</div>
            <div class="radek">{{ \Illuminate\Support\Str::limit($z->zakaznik?->nazev, 22) }}</div>
            @if ($prvni)
                <div class="radek"><b>{{ \Illuminate\Support\Str::limit($prvni->oznaceni, 22) }}</b>@if ($zarizeni->count() > 1) +{{ $zarizeni->count() - 1 }}@endif</div>
                @if ($prvni->seriove_cislo)<div class="radek">SN {{ \Illuminate\Support\Str::limit($prvni->seriove_cislo, 20) }}</div>@endif
            @endif
            <div class="radek">Přijato {{ optional($z->datum_prijeti)->format('d.m.Y') }}</div>
        </td>
        <td class="qr">
            @if ($qr)<img src="{{ $qr }}" alt="QR">@endif
            <div class="firma">Konzolák Zlín</div>
        </td>
    </tr>
</table>
</body>
</html>
