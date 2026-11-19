<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">
<style>
    {{-- štítek 50 × 30 mm na zápůjční zařízení: vlevo údaje, vpravo QR (odkaz na zařízení v administraci) --}}
    * { font-family: dejavusans, sans-serif; }
    body { margin: 0; color: #0F2038; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 0; }
    .typ { font-size: 6pt; font-weight: bold; color: #fff; background-color: #0F2038; padding: .4mm 1mm; letter-spacing: .03em; }
    .nazev { font-size: 7pt; font-weight: bold; line-height: 1.15; margin-top: .8mm; }
    .radek { font-size: 6pt; line-height: 1.2; margin-top: .4mm; }
    .majetek { font-size: 5.5pt; color: #444; margin-top: .8mm; line-height: 1.2; }
    .qr { width: 23mm; text-align: center; vertical-align: top; }
    .qr img { width: 23mm; height: 23mm; }
    .firma { font-size: 7.5pt; font-weight: bold; color: #9a7420; text-align: center; margin-top: -.6mm; }
</style>
</head>
<body>
@foreach ($zarizeni as $s)
    @php($d = $s['d'])
    @if (! $loop->first)<pagebreak />@endif
    <table>
        <tr>
            <td style="vertical-align:top">
                <span class="typ">ZÁPŮJČNÍ ZAŘÍZENÍ</span>
                <div class="nazev">{{ $d->nazev }}</div>
                @if ($d->seriove_cislo)<div class="radek">SN&nbsp;{{ $d->seriove_cislo }}</div>@endif
                <div class="radek">Ev. č. Z-{{ str_pad($d->id, 3, '0', STR_PAD_LEFT) }}</div>
                <div class="majetek">Majetek servisu, neprodávat<br>tel. {{ $firma->telefon ?: '778 796 868' }}</div>
            </td>
            <td class="qr">
                @if ($s['qr'])<img src="{{ $s['qr'] }}" alt="QR">@endif
                <div class="firma">Konzolák Zlín</div>
            </td>
        </tr>
    </table>
@endforeach
</body>
</html>
