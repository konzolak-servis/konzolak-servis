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
    <b>Podmínky zápůjčky:</b> Vypůjčitel převzal výše uvedená zařízení ve funkčním stavu a v uvedené kompletnosti.
    Zavazuje se je užívat s péčí řádného hospodáře, nepřenechat je třetí osobě, neotevírat je a vrátit je
    {{ $z->vratit_do ? 'nejpozději ' . $z->vratit_do->format('d.m.Y') : 'v dohodnutém termínu' }}
    kompletní a ve stavu odpovídajícím běžnému opotřebení.
    @if ((float) $z->kauce > 0)
        Na zápůjčku složil vratnou kauci <b>{{ number_format((float) $z->kauce, 0, ',', ' ') }} Kč</b>, která mu bude vrácena při vrácení zařízení.
        Z kauce může být sražena částka odpovídající poškození, ztrátě příslušenství nebo nevrácení zařízení.
    @endif
    @if ($z->zdarma)
        Zápůjčka je po dobu opravy{{ $z->zakazka ? ' zakázky ' . $z->zakazka->cislo : '' }} <b>zdarma</b>; po jejím dokončení je vypůjčitel povinen zařízení bez prodlení vrátit.
    @elseif ((float) $z->denni_sazba > 0 || $z->maVikendovyTarif())
        Cena zapůjčení: <b>{{ $z->tarifText() }}</b>, účtuje se každý započatý den; vyúčtována (stržena z kauce) bude při vrácení.
    @endif
    Za ztrátu, zničení nebo poškození nad rámec běžného opotřebení odpovídá vypůjčitel do výše hodnoty zařízení.
</div>

@include('pdf.partials.paticka', [
    'podpisL' => 'Předal za servis',
    'podpisR' => 'Převzal a souhlasí s podmínkami (podpis)',
    'doklad' => 'Předávací protokol ' . $z->cislo,
    'gdpr' => true,
])

@include('pdf.partials.konec')
</body>
</html>
