<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@include('pdf.partials.hlavicka', ['nadpis' => 'Protokol o vrácení zápůjčky', 'cislo' => $z->cislo])

@include('pdf.partials.zapujcka-hlava')

<div class="sekce">Vrácená zařízení – vráceno dne {{ $z->datum_vraceni?->format('d.m.Y') ?: now()->format('d.m.Y') }}</div>
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

@if ($z->poznamka_vraceni)
    <div class="sekce">Poznámka</div>
    <div class="box">{!! nl2br(e($z->poznamka_vraceni)) !!}</div>
@endif

<div class="sekce">Vypořádání kauce</div>
<table class="totalbar">
    <tr>
        <td class="tlabel">Přijatá kauce</td>
        <td class="tsum" width="45mm" style="border-top:.8px solid #d9dee5">{{ number_format((float) $z->kauce, 2, ',', ' ') }} Kč</td>
    </tr>
    @if ((float) $z->kauce_srazka > 0)
        <tr>
            <td class="tlabel">Sraženo{{ $z->srazka_duvod ? ' – ' . $z->srazka_duvod : '' }}</td>
            <td class="tsum" width="45mm" style="border-top:.8px solid #d9dee5">− {{ number_format((float) $z->kauce_srazka, 2, ',', ' ') }} Kč</td>
        </tr>
    @endif
    <tr>
        <td class="tlabel"><b>Vráceno vypůjčiteli</b></td>
        <td class="tsum" width="45mm">{{ number_format($z->vracenaKauce(), 2, ',', ' ') }} Kč</td>
    </tr>
</table>

<div class="upozorneni">
    Servis potvrzuje převzetí výše uvedených zařízení. Vypůjčitel svým podpisem potvrzuje převzetí
    vrácené kauce ve výši <b>{{ number_format($z->vracenaKauce(), 0, ',', ' ') }} Kč</b>{{ (float) $z->kauce_srazka > 0 ? ' a souhlas se srážkou ' . number_format((float) $z->kauce_srazka, 0, ',', ' ') . ' Kč' : '' }};
    tím je zápůjčka vypořádána.
</div>

@include('pdf.partials.paticka', [
    'podpisL' => 'Zařízení převzal za servis',
    'podpisR' => 'Kauci převzal vypůjčitel (podpis)',
    'doklad' => 'Protokol o vrácení ' . $z->cislo,
])

@include('pdf.partials.konec')
</body>
</html>
