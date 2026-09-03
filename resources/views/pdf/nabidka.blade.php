<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@include('pdf.partials.hlavicka', ['nadpis' => 'Cenová nabídka', 'cislo' => $n->cislo])

@php($zk = $n->zakaznik)

<table class="cols" style="margin-bottom:3mm">
    <tr>
        <td width="55%">
            <div class="label">Pro</div>
            <div class="val"><strong>{{ $zk?->nazev }}</strong></div>
            @if ($zk?->telefon)<div class="muted">tel. {{ $zk->telefon }}</div>@endif
            @if ($zk?->email)<div class="muted">{{ $zk->email }}</div>@endif
        </td>
        <td width="45%">
            <table class="cols">
                <tr>
                    <td><div class="label">Datum</div><div class="val">{{ optional($n->datum)->format('d. m. Y') }}</div></td>
                    <td><div class="label">Platnost do</div><div class="val">{{ optional($n->platnost_do)->format('d. m. Y') }}</div></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="items">
    <tr>
        <th style="width:20%">Komponenta</th><th>Model</th>
        <th style="width:12%">Varianta</th>
        <th class="num" style="width:9%">Ks</th>
        <th class="num" style="width:14%">Cena</th>
        <th class="num" style="width:14%">Celkem</th>
    </tr>
    @foreach ($n->polozky as $p)
        <tr>
            <td>{{ $p->skupina }}</td>
            <td>{{ $p->popis }}</td>
            <td>{{ \App\Models\NabidkaPolozka::VARIANTY[$p->varianta] ?? '' }}</td>
            <td class="num">{{ rtrim(rtrim(number_format($p->mnozstvi, 3, ',', ' '), '0'), ',') }}</td>
            <td class="num">{{ number_format($p->cena, 0, ',', ' ') }} Kč</td>
            <td class="num">{{ number_format($p->cena_celkem, 0, ',', ' ') }} Kč</td>
        </tr>
    @endforeach
</table>

<table class="totalbar">
    <tr>
        <td class="tlabel">
            @if ($n->zaloha > 0)Záloha {{ number_format($n->zaloha, 0, ',', ' ') }} Kč@endif
        </td>
        <td class="tsum">
            {{ number_format($n->celkem, 0, ',', ' ') }} Kč
            <div style="font-size:6.5pt;font-weight:normal;color:#9ca3af">CELKEM</div>
        </td>
    </tr>
</table>

@if ($firma->pravni_text_nabidka)
    <div class="pravni">{{ $firma->pravni_text_nabidka }}</div>
@endif

@include('pdf.partials.paticka', [
    'podpisL' => 'Za servis (razítko a podpis)',
    'podpisR' => 'Souhlas zákazníka (podpis)',
    'doklad' => 'Nabídka ' . $n->cislo,
])

@include('pdf.partials.konec')
</body>
</html>
