<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">
<style>
    {{-- štítek 50 × 30 mm (okraje 1,5 mm): vlevo údaje, vpravo QR 23 mm --}}
    * { font-family: dejavusans, sans-serif; }
    body { margin: 0; color: #0F2038; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 0; }
    .cislo { font-size: 9.5pt; font-weight: bold; }
    .radek { font-size: 6.5pt; line-height: 1.2; margin-top: .5mm; }
    .radek b { color: #9a7420; }
    .qr { width: 23mm; text-align: center; vertical-align: top; }
    .qr img { width: 23mm; height: 23mm; }
    .firma { font-size: 7.5pt; font-weight: bold; color: #9a7420; text-align: center; margin-top: -.6mm; }
</style>
</head>
<body>
@php($zarizeni = $z->vsechnaZarizeni())
<table>
    <tr>
        <td style="vertical-align:top">
            <div class="cislo">{{ $z->cislo }}</div>
            <div class="radek">{{ $z->zakaznik?->nazev }}</div>
            @foreach ($zarizeni as $zar)
                <div class="radek"><b>{{ $zar->oznaceni }}</b></div>
                @if ($zar->seriove_cislo)<div class="radek">SN&nbsp;{{ $zar->seriove_cislo }}</div>@endif
            @endforeach
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
