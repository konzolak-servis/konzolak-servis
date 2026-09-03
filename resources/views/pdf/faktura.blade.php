<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@include('pdf.partials.hlavicka', ['nadpis' => 'Faktura', 'cislo' => $f->cislo, 'skrytFirmaRadek' => true])

@php($zk = $f->zakaznik)

<table class="cols" style="margin-bottom:3mm">
    <tr>
        <td width="50%">
            <div class="label">Dodavatel</div>
            <div class="val"><strong>{{ $firma->nazev }}</strong></div>
            <div>{{ trim(($firma->ulice ? $firma->ulice . ', ' : '') . trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? ''))) }}</div>
            @if ($firma->ico)<div>IČO: {{ $firma->ico }}</div>@endif
            @if (! $firma->platce_dph)<div class="muted">Neplátce DPH</div>@endif
        </td>
        <td width="50%">
            <div class="label">Odběratel</div>
            <div class="val"><strong>{{ $zk?->nazev }}</strong></div>
            @if ($zk?->adresa_radek)<div>{{ $zk->adresa_radek }}</div>@endif
            @if ($zk?->ico)<div>IČO: {{ $zk->ico }}@if ($zk->dic) · DIČ: {{ $zk->dic }}@endif</div>@endif
            @if ($zk?->telefon)<div class="muted">tel. {{ $zk->telefon }}</div>@endif
            @if ($zk?->email)<div class="muted">{{ $zk->email }}</div>@endif
        </td>
    </tr>
</table>

<table class="items" style="margin-bottom:3mm">
    <tr>
        <th>Datum vystavení</th><th>Datum splatnosti</th><th>Forma úhrady</th>
        <th>Variabilní symbol</th>@if ($firma->cislo_uctu)<th>Číslo účtu</th>@endif
    </tr>
    <tr>
        <td>{{ optional($f->datum_vystaveni)->format('d. m. Y') }}</td>
        <td>{{ optional($f->datum_splatnosti)->format('d. m. Y') }}</td>
        <td>{{ ['převodem' => 'Na účet', 'hotově' => 'Hotově', 'kartou' => 'Kartou'][$f->forma_uhrady] ?? $f->forma_uhrady }}</td>
        <td>{{ $f->variabilni_symbol }}</td>
        @if ($firma->cislo_uctu)<td>{{ $firma->cislo_uctu }}</td>@endif
    </tr>
</table>

<table class="items">
    <tr>
        <th style="width:26%">Zařízení</th><th>Popis</th>
        <th class="num" style="width:12%">Množství</th>
        <th class="num" style="width:15%">Cena</th>
        <th class="num" style="width:15%">Celkem</th>
    </tr>
    @foreach ($f->polozky as $p)
        <tr>
            <td>{{ $p->zarizeni_text }}</td>
            <td>{{ $p->popis }}</td>
            <td class="num">{{ rtrim(rtrim(number_format($p->mnozstvi, 3, ',', ' '), '0'), ',') }}</td>
            <td class="num">{{ number_format($p->cena, 0, ',', ' ') }} Kč</td>
            <td class="num">{{ number_format($p->cena_celkem, 0, ',', ' ') }} Kč</td>
        </tr>
    @endforeach
</table>

<table class="totalbar">
    <tr>
        <td class="tlabel" style="vertical-align:bottom">
            @if (! empty($qrPlatba))
                <table><tr>
                    <td style="width:26mm"><img src="{{ $qrPlatba }}" style="width:24mm;height:24mm"></td>
                    <td style="vertical-align:middle;font-size:8pt;color:#374151">
                        <strong>QR Platba</strong><br>
                        Naskenujte v bankovní aplikaci –<br>částka i variabilní symbol se vyplní samy.
                    </td>
                </tr></table>
            @endif
        </td>
        <td class="tsum">
            {{ number_format($f->celkem, 0, ',', ' ') }} Kč
            <div style="font-size:6.5pt;font-weight:normal;color:#9ca3af">CELKEM K ÚHRADĚ</div>
        </td>
    </tr>
</table>

@unless ($firma->platce_dph)
    <div style="font-size:8pt;color:#374151;margin-top:2mm;font-weight:bold">
        Dodavatel není plátcem DPH – fakturovaná částka je konečná.
    </div>
@endunless

@if ($firma->pravni_text_faktura)
    <div class="pravni">{{ $firma->pravni_text_faktura }}</div>
@endif

@include('pdf.partials.paticka', [
    'podpisL' => 'Vystavil (razítko a podpis)',
    'doklad' => 'Faktura ' . $f->cislo,
])

@include('pdf.partials.konec')
</body>
</html>
