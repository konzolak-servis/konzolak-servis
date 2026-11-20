<x-mail-layout :firma="$firma" :nadpis="$z->stav === 'vraceno' ? 'Vrácení zápůjčky' : 'Zápůjčka zařízení'">
    <p style="margin:0 0 14px;">Dobrý den,</p>

    @if ($z->stav === 'vraceno')
        <p style="margin:0 0 14px;">
            děkujeme za vrácení zapůjčeného zařízení ({{ $z->polozky->pluck('nazev')->implode(', ') }}).
            V příloze zasíláme <strong>protokol o vrácení zařízení</strong> a <strong>vyúčtování kauce</strong>
            k zápůjčce <strong>{{ $z->cislo }}</strong>.
        </p>
        <p style="margin:0 0 14px;">
            @if ($z->doplatek() > 0)
                Doplatek: <strong style="color:#0F2038;">{{ number_format($z->doplatek(), 0, ',', ' ') }} Kč</strong>
            @else
                Vrácená kauce: <strong style="color:#0F2038;">{{ number_format($z->vracenaKauce(), 0, ',', ' ') }} Kč</strong>
            @endif
        </p>
    @else
        <p style="margin:0 0 14px;">
            v příloze zasíláme <strong>předávací protokol</strong> k zápůjčce <strong>{{ $z->cislo }}</strong>
            ({{ $z->polozky->pluck('nazev')->implode(', ') }}){{ (float) $z->kauce > 0 ? ' a doklad o přijetí kauce' : '' }}.
            V protokolu najdete i pravidla zápůjčky.
        </p>
        <p style="margin:0 0 14px;">
            Vrátit prosím
            <strong style="color:#0F2038;">{{ $z->vratit_do ? 'nejpozději ' . $z->vratit_do->format('d.m.Y') : 'v dohodnutém termínu' }}</strong>.
            @if ($z->zdarma)
                Zápůjčka je po dobu opravy zdarma.
            @elseif ((float) $z->denni_sazba > 0 || $z->maVikendovyTarif())
                Cena: {{ $z->tarifText() }}.
            @endif
        </p>
        @if ($firma->telefon)
            <p style="margin:0 0 14px; font-size:14px;">Při jakékoli závadě nebo dotazu volejte <strong>{{ $firma->telefon }}</strong>.</p>
        @endif
    @endif

    <p style="margin:16px 0 0;">Děkujeme a přejeme hezké hraní.</p>
    <p style="margin:8px 0 0;">S pozdravem,<br>{{ $firma->nazev }}</p>
</x-mail-layout>
