<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">
<style>
    * { font-family: dejavusans, sans-serif; }
    body { margin: 0; color: #0F2038; }
    table { width: 100%; border-collapse: collapse; }
    .cislo { font-size: 15pt; font-weight: bold; letter-spacing: .02em; }
    .radek { font-size: 8pt; margin-top: .6mm; }
    .radek b { color: #9a7420; }
    .qr { width: 22mm; text-align: right; vertical-align: top; }
    .qr img { width: 21mm; height: 21mm; }
</style>
</head>
<body>
<table>
    <tr>
        <td style="vertical-align:top">
            <div class="cislo">{{ $z->cislo }}</div>
            <div class="radek"><b>Zákazník:</b> {{ $z->zakaznik?->nazev }}</div>
            <div class="radek"><b>Zařízení:</b> {{ $z->zarizeni?->oznaceni ?: '—' }}</div>
            @if ($z->zarizeni?->seriove_cislo)
                <div class="radek"><b>SN:</b> {{ $z->zarizeni->seriove_cislo }}</div>
            @endif
            <div class="radek"><b>Přijato:</b> {{ optional($z->datum_prijeti)->format('d.m.Y') }}</div>
            @if ($z->predpokladana_cena)
                <div class="radek"><b>Odhad:</b> {{ number_format($z->predpokladana_cena, 0, ',', ' ') }} Kč</div>
            @endif
        </td>
        <td class="qr">
            @if ($qr)<img src="{{ $qr }}" alt="QR">@endif
            <div style="font-size:6pt;color:#888;margin-top:.5mm">Konzolák Zlín</div>
        </td>
    </tr>
</table>
</body>
</html>
