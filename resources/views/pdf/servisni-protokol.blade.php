<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@include('pdf.partials.hlavicka', ['nadpis' => 'Servisní protokol', 'cislo' => $z->cislo])

@if ($z->reklamace_k_id && $z->reklamaceK)
    <div class="upozorneni" style="margin-top:0">
        <b>Reklamace</b> původní zakázky {{ $z->reklamaceK->cislo }}.
    </div>
@endif

@include('pdf.partials.zarizeni')

@if ($z->zarukaDo())
    <div class="muted" style="margin-top:-1mm;font-size:8pt">
        Záruka na provedenou opravu do <strong>{{ $z->zarukaDo()->format('d. m. Y') }}</strong>.
    </div>
@endif

<div class="sekce">Zjištěná závada</div>
<div class="box">{!! nl2br(e($z->zjistena_zavada ?: $z->popis_zavady ?: '—')) !!}</div>

<div class="sekce">Provedené práce</div>
<div class="box">{!! nl2br(e($z->navrh_reseni_prace ?: '—')) !!}</div>

@php($radky = $z->polozky->where('uctovat', true))
@if ($radky->count())
    <div class="sekce">Práce a účtované položky</div>
    <table class="items">
        <tr>
            <th style="width:52%">Popis</th>
            <th class="num" style="width:14%">Množství</th>
            <th class="num" style="width:17%">Cena/ks</th>
            <th class="num" style="width:17%">Celkem</th>
        </tr>
        @foreach ($radky as $p)
            <tr>
                <td>{{ $p->nazev }}</td>
                <td class="num">{{ rtrim(rtrim(number_format($p->mnozstvi, 3, ',', ' '), '0'), ',') }}</td>
                <td class="num">{{ number_format($p->cena_ks, 0, ',', ' ') }} Kč</td>
                <td class="num">{{ number_format($p->cena_celkem, 0, ',', ' ') }} Kč</td>
            </tr>
        @endforeach
    </table>
@endif

<table class="totalbar">
    <tr>
        <td class="tlabel" style="vertical-align:bottom">
            @if (! empty($qrPlatba))
                <table><tr>
                    <td style="width:24mm"><img src="{{ $qrPlatba }}" style="width:22mm;height:22mm"></td>
                    <td style="vertical-align:middle;font-size:7.5pt;color:#374151">
                        <strong>QR Platba</strong> – naskenujte<br>v bankovní aplikaci.
                    </td>
                </tr></table>
            @elseif ($z->zpusob_uhrady === 'hotove' && max($z->cena_celkem - $z->zaloha, 0) > 0)
                <strong>Platba hotově</strong> při vyzvednutí.
                @if ($z->zaloha > 0)
                    <br>Cena celkem {{ number_format($z->cena_celkem, 0, ',', ' ') }} Kč &nbsp;−&nbsp;
                    záloha {{ number_format($z->zaloha, 0, ',', ' ') }} Kč
                @endif
            @elseif ($z->zaloha > 0)
                Cena celkem {{ number_format($z->cena_celkem, 0, ',', ' ') }} Kč &nbsp;−&nbsp;
                záloha {{ number_format($z->zaloha, 0, ',', ' ') }} Kč
            @endif
        </td>
        <td class="tsum">
            {{ number_format(max($z->cena_celkem - $z->zaloha, 0), 0, ',', ' ') }} Kč
            <div style="font-size:6.5pt;font-weight:normal;color:#9ca3af">K ÚHRADĚ</div>
        </td>
    </tr>
</table>

@if ($z->poznamka && $z->poznamka !== 'UKÁZKA')
    <div class="sekce">Poznámka</div>
    <div class="box">{!! nl2br(e($z->poznamka)) !!}</div>
@endif

@if ($firma->pravni_text_protokol)
    <div class="pravni">{{ $firma->pravni_text_protokol }}</div>
@endif

@include('pdf.partials.paticka', [
    'podpisL' => 'Za servis (razítko a podpis)',
    'podpisR' => 'Zařízení převzal zákazník (podpis)',
    'doklad' => 'Servisní protokol ' . $z->cislo,
    'gdpr' => true,
])

@include('pdf.partials.konec')
</body>
</html>
