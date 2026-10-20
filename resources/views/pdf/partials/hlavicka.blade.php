@php
    $kontakt = array_values(array_filter([
        trim(($firma->ulice ? $firma->ulice . ', ' : '') . trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? ''))),
        $firma->ico ? 'IČO ' . $firma->ico : null,
        $firma->dic ? 'DIČ ' . $firma->dic : null,
        $firma->telefon ? 'tel. ' . $firma->telefon : null,
        $firma->email ?: null,
        $firma->cislo_uctu ? 'č. ú. ' . $firma->cislo_uctu : null,
        $firma->platce_dph ? null : 'neplátce DPH',
    ]));
@endphp
{{-- Záhlaví pro další stránky (první stránka má plný hlavičkový pruh níže) + průběžná patička s číslem stránky --}}
<htmlpageheader name="dalsi">
    <table class="band band-mini" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="band-logo"><img src="{{ public_path('images/konzolak-logo-print.png') }}" height="34"></td>
            <td class="band-doc"><span class="typ-mini">{{ $nadpis }}@isset($cislo) · č. {{ $cislo }}@endisset</span></td>
        </tr>
    </table>
    <div class="goldrule"></div>
</htmlpageheader>
<htmlpagefooter name="meta">
    <div class="paticka">
        <div class="meta">
            {{ $firma->nazev ?: 'Konzolák Zlín' }} · {{ $nadpis }}@isset($cislo) {{ $cislo }}@endisset · strana {PAGENO}/{nbpg}
        </div>
    </div>
</htmlpagefooter>
<sethtmlpagefooter name="meta" value="on" show-this-page="1" />
<table class="band" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td class="band-logo"><img src="{{ public_path('images/konzolak-logo-print.png') }}" height="66"></td>
        <td class="band-doc">
            <span class="typ">{{ $nadpis }}</span>
            @isset($cislo)<br><span class="num">č. {{ $cislo }}</span>@endisset
        </td>
    </tr>
    @unless (! empty($skrytFirmaRadek))
        <tr>
            <td class="band-firma" colspan="2">
                <strong>{{ $firma->nazev ?: 'Konzolák Zlín' }}</strong>&nbsp;·&nbsp;{!! implode(' &nbsp;·&nbsp; ', array_map('e', $kontakt)) !!}
            </td>
        </tr>
    @endunless
</table>
<div class="goldrule"></div>

<div class="wrap">
