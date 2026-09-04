<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@include('pdf.partials.hlavicka', ['nadpis' => 'Servisní doklad', 'cislo' => $z->cislo])

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:4mm">
    <tr>
        <td style="vertical-align:middle">
            <div class="muted">
                Potvrzení o převzetí zařízení do opravy.<br>
                <b>Tento doklad předložte při vyzvednutí.</b>
                @if (! empty($qr))
                    <br>Aktuální stav opravy zjistíte naskenováním QR kódu&nbsp;→
                @endif
            </div>
        </td>
        @if (! empty($qr))
            <td width="28mm" style="text-align:right; vertical-align:middle">
                <img src="{{ $qr }}" style="width:24mm; height:24mm">
                <div style="font-size:6pt; color:#6b7280; text-align:center; margin-top:.5mm; line-height:1.25">
                    Sledování stavu opravy<br>{{ $z->cislo }}
                </div>
            </td>
        @endif
    </tr>
</table>

@include('pdf.partials.zarizeni')

<div class="sekce">Popis závady (dle zákazníka)</div>
<div class="box">{!! nl2br(e($z->popis_zavady ?: '—')) !!}</div>

@if ($z->pozadovane_reseni)
    <div class="sekce">Požadované řešení</div>
    <div class="box">{!! nl2br(e($z->pozadovane_reseni)) !!}</div>
@endif

@if ($z->prislusenstvi)
    <div class="sekce">Předané příslušenství</div>
    <div class="box">{!! nl2br(e($z->prislusenstvi)) !!}</div>
@endif

<table class="cols" style="margin-top:4mm">
    <tr>
        <td width="33%"><div class="label">Předpokládaná cena</div>
            <div class="val"><strong>{{ $z->predpokladana_cena ? number_format($z->predpokladana_cena, 0, ',', ' ') . ' Kč' : 'bude upřesněna' }}</strong></div></td>
        <td width="33%"><div class="label">Přijatá záloha</div>
            <div class="val"><strong>{{ number_format($z->zaloha, 0, ',', ' ') }} Kč</strong></div></td>
        <td width="34%"><div class="label">Stav zakázky</div>
            <div class="val">{{ $z->stav_nazev }}</div></td>
    </tr>
</table>

<div class="upozorneni">
    <b>Vyzvednutí:</b> Opravené zařízení bude vydáno pouze po předložení tohoto dokladu.
    Servis neručí za data uložená na médiích – jejich zálohování je odpovědností zákazníka.
    Nevyzvednuté zařízení bude po 6 měsících od výzvy k převzetí uskladněno na náklady zákazníka, případně prodáno.
</div>

@if ($firma->pravni_text_servisni_list)
    <div class="pravni">{{ $firma->pravni_text_servisni_list }}</div>
@endif

@include('pdf.partials.paticka', [
    'podpisL' => 'Převzal za servis',
    'podpisR' => 'Předal zákazník (podpis)',
    'doklad' => 'Servisní doklad ' . $z->cislo,
    'gdpr' => true,
])

@include('pdf.partials.konec')
</body>
</html>
