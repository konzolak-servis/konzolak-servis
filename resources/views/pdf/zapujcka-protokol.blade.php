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

@php
    // poznámky typů zapůjčených zařízení (např. PS5 v továrním nastavení) – každá jen jednou
    $typy = $z->polozky->map(fn ($p) => $p->zapujcniZarizeni?->typ)->filter(fn ($t) => filled($t?->protokol_poznamka))->unique('id');
@endphp
@if ($typy->isNotEmpty())
    <div class="box" style="font-size:8pt; line-height:1.35; margin-top:3mm; border-left:2px solid #0F2038">
        @foreach ($typy as $t)
            <b style="color:#0F2038">{{ $typy->count() > 1 ? $t->nazev : 'Důležité k zařízení' }}:</b> {{ $t->protokol_poznamka }}
            @if (! $loop->last)<br>@endif
        @endforeach
    </div>
@endif

<div class="upozorneni" style="margin-top:3mm; font-size:8.3pt">
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
    @if ($z->poznamka)
        <br><b>Poznámka:</b> {!! nl2br(e($z->poznamka)) !!}
    @endif
    <br><b>Podpisem vypůjčitel potvrzuje převzetí zařízení a souhlas s pravidly zápůjčky uvedenými níže.</b>
</div>

@php
    $pravidla = collect(preg_split('/\R/', (string) $firma->zapujcka_pravidla))
        ->map(fn ($r) => trim(str_replace('{telefon}', $firma->telefon ?: '778 796 868', $r)))
        ->filter()->values();
@endphp
@if ($pravidla->isNotEmpty())
    <div class="sekce" style="margin-top:3.5mm">Pravidla zápůjčky</div>
    {{-- dva sloupce, ať se protokol vejde na jednu stránku --}}
    @php($sloupce = $pravidla->chunk((int) ceil($pravidla->count() / 2)))
    <table width="100%" cellpadding="0" cellspacing="0" style="font-size:7.3pt; line-height:1.3; color:#374151">
        <tr>
            @foreach ($sloupce as $s => $sloupec)
                <td width="50%" style="vertical-align:top; {{ $s === 0 ? 'padding-right:3mm' : 'padding-left:3mm' }}">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        @foreach ($sloupec as $i => $r)
                            <tr>
                                <td width="5.5mm" style="vertical-align:top; padding:0 0 .9mm; color:#9a7420; font-weight:bold">{{ $i + 1 }}.</td>
                                <td style="vertical-align:top; padding:0 0 .9mm">{{ $r }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            @endforeach
        </tr>
    </table>

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
