<x-mail-layout :firma="$firma" nadpis="Servisní doklad">
    <p style="margin:0 0 14px;">Dobrý den,</p>

    <p style="margin:0 0 14px;">
        potvrzujeme převzetí zařízení do opravy. V příloze zasíláme <strong>servisní doklad</strong>
        k zakázce <strong>{{ $z->cislo }}</strong>@if ($z->zarizeni) – {{ $z->zarizeni->oznaceni }}@endif.
    </p>

    <p style="margin:0 0 14px; font-size:13px; color:#6b7280;">
        Doklad si prosím uschovejte – <strong>předložte ho při vyzvednutí</strong> opraveného zařízení.
    </p>

    @if ($z->predpokladana_cena)
        <p style="margin:0 0 14px;">
            Předpokládaná cena opravy:
            <strong style="color:#0F2038;">{{ number_format($z->predpokladana_cena, 0, ',', ' ') }} Kč</strong>
            <span style="font-size:12px; color:#6b7280;">(bude upřesněna po diagnostice)</span>
        </p>
    @endif

    @if ($z->zaloha > 0)
        <p style="margin:0 0 14px; font-size:13px; color:#374151;">
            Přijatá záloha: <strong>{{ number_format($z->zaloha, 0, ',', ' ') }} Kč</strong>
        </p>
    @endif

    <p style="margin:0 0 14px; font-size:13px; color:#6b7280;">
        Servis neručí za data uložená na médiích – jejich zálohování je odpovědností zákazníka.
    </p>

    <p style="margin:16px 0 0;">Ozveme se, jakmile bude zakázka vyřízená.</p>
    <p style="margin:8px 0 0;">S pozdravem,<br>{{ $firma->nazev }}</p>
</x-mail-layout>
