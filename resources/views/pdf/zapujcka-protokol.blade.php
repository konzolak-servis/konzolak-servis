<!DOCTYPE html>
<html lang="cs">
<head><meta charset="utf-8">@include('pdf.partials.styl')</head>
<body>
@include('pdf.partials.hlavicka', ['nadpis' => 'Předávací protokol – zápůjčka', 'cislo' => $z->cislo])

@include('pdf.partials.zapujcka-hlava')

<div class="sekce">Zapůjčená zařízení</div>
<table class="items">
    <tr><th>#</th><th>Zařízení</th><th>Sériové číslo</th><th>Příslušenství</th><th>Stav při předání</th><th class="num">Hodnota</th></tr>
    @foreach ($z->polozky as $p)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td><b>{{ $p->nazev }}</b></td>
            <td>{{ $p->seriove_cislo ?: '—' }}</td>
            <td>{{ $p->prislusenstvi ?: '—' }}</td>
            <td>{{ $p->stav_pri_zapujceni ?: 'funkční' }}</td>
            <td class="num">{{ $p->hodnota ? number_format((float) $p->hodnota, 0, ',', ' ') . ' Kč' : '—' }}</td>
        </tr>
    @endforeach
</table>

@if ($z->poznamka)
    <div class="sekce">Poznámka</div>
    <div class="box">{!! nl2br(e($z->poznamka)) !!}</div>
@endif

<div class="upozorneni">
    <b>Dohoda:</b> Vypůjčitel převzal výše uvedená zařízení ve funkčním stavu a kompletní a vrátí je
    <b>{{ $z->vratit_do ? 'nejpozději ' . $z->vratit_do->format('d.m.Y') : 'v dohodnutém termínu' }}</b>.
    @if ((float) $z->kauce > 0)
        Složil vratnou kauci <b>{{ number_format((float) $z->kauce, 0, ',', ' ') }} Kč</b>.
    @endif
    @if ($z->zdarma)
        Zápůjčka je po dobu opravy{{ $z->zakazka ? ' zakázky ' . $z->zakazka->cislo : '' }} <b>zdarma</b>.
    @elseif ((float) $z->denni_sazba > 0 || $z->maVikendovyTarif())
        Cena zapůjčení: <b>{{ $z->tarifText() }}</b> (každý započatý den), vyúčtuje se při vrácení.
    @endif
</div>

@php
    $pravidla = collect(preg_split('/\R/', (string) $firma->zapujcka_pravidla))
        ->map(fn ($r) => trim(str_replace('{telefon}', $firma->telefon ?: '778 796 868', $r)))
        ->filter()->values();
@endphp
@if ($pravidla->isNotEmpty())
    <div class="sekce">Pravidla zápůjčky</div>
    <table width="100%" cellpadding="0" cellspacing="0" style="font-size:7.8pt; line-height:1.35; color:#374151">
        @foreach ($pravidla as $r)
            <tr>
                <td width="6.5mm" style="vertical-align:top; padding:0 0 .8mm; color:#9a7420; font-weight:bold">{{ $loop->iteration }}.</td>
                <td style="vertical-align:top; padding:0 0 .8mm">{{ $r }}</td>
            </tr>
        @endforeach
    </table>
    <div style="font-size:7.8pt; margin-top:1.5mm; color:#0F2038"><b>Podpisem vypůjčitel potvrzuje, že pravidla četl a souhlasí s nimi.</b></div>
@endif

@include('pdf.partials.paticka', [
    'podpisL' => 'Předal za servis',
    'podpisR' => 'Převzal a souhlasí s podmínkami (podpis)',
    'doklad' => 'Předávací protokol ' . $z->cislo,
    'gdpr' => true,
])

@include('pdf.partials.konec')
</body>
</html>
