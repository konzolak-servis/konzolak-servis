<x-mail-layout :firma="$firma" nadpis="Servisní protokol">
    <p style="margin:0 0 14px;">Dobrý den,</p>

    <p style="margin:0 0 14px;">
        v příloze zasíláme <strong>servisní protokol</strong> k zakázce
        <strong>{{ $z->cislo }}</strong>@if ($z->zarizeni) – {{ $z->zarizeni->oznaceni }}@endif.
    </p>

    @if (($z->cena_celkem - $z->zaloha) > 0)
        <p style="margin:0 0 12px;">
            K úhradě:
            <strong style="color:#0F2038;">{{ number_format(max($z->cena_celkem - $z->zaloha, 0), 0, ',', ' ') }} Kč</strong>
            @if ($z->zpusob_uhrady === 'ucet') – platba na účet.
            @elseif ($z->zpusob_uhrady === 'hotove') – hotově při vyzvednutí.
            @endif
        </p>

        @if ($z->zpusob_uhrady === 'ucet' && $firma->cislo_uctu)
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:4px 0 14px;">
                <tr>
                    <td style="padding-right:16px; vertical-align:middle;">
                        <img src="{{ route('qr.zakazka', ['zakazka' => $z->id, 'token' => \App\Support\QrPlatba::token('zakazka', $z->id)]) }}"
                             alt="QR platba" width="150" height="150"
                             style="width:150px; height:150px; border:1px solid #ece6d6; border-radius:6px; background:#fff;">
                    </td>
                    <td style="vertical-align:middle; font-size:13px; color:#5f5a4e; line-height:1.5;">
                        <strong style="color:#0F2038;">QR platba</strong><br>
                        Naskenujte v bankovní aplikaci.
                    </td>
                </tr>
            </table>
        @endif
    @endif

    @if ($z->zaruka_mesice)
        <p style="margin:0 0 14px; font-size:13px; color:#6b7280;">
            Záruka na provedenou opravu: <strong>{{ $z->zaruka_mesice }}</strong>
            {{ $z->zaruka_mesice == 1 ? 'měsíc' : ($z->zaruka_mesice < 5 ? 'měsíce' : 'měsíců') }}.
        </p>
    @endif

    @if ($firma->email_vyzvednuti)
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
               style="margin:14px 0 6px; background:#f6f8fb; border:1px solid #e2e8f0; border-left:3px solid #0F2038; border-radius:8px;">
            <tr>
                <td style="padding:14px 18px;">
                    <div style="font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:.04em; color:#0F2038; margin-bottom:8px;">
                        Vyzvednutí zařízení
                    </div>
                    <div style="font-size:14px; color:#374151; line-height:1.6;">
                        {!! nl2br(e($firma->email_vyzvednuti)) !!}
                    </div>
                    @if ($firma->telefon)
                        <div style="font-size:14px; color:#0F2038; margin-top:8px;"><strong>Tel.: {{ $firma->telefon }}</strong></div>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:16px 0 0;">Děkujeme, že využíváte našich služeb.</p>
    <p style="margin:8px 0 0;">S pozdravem,<br>{{ $firma->nazev }}</p>
</x-mail-layout>
