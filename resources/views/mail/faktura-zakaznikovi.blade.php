<x-mail-layout :firma="$firma" nadpis="Faktura {{ $f->cislo }}">
    <p style="margin:0 0 14px;">Dobrý den,</p>

    <p style="margin:0 0 14px;">
        v příloze zasíláme fakturu <strong>{{ $f->cislo }}</strong>@if ($f->zakazka) k zakázce
        <strong>{{ $f->zakazka->cislo }}</strong>@endif.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
           style="margin:18px 0; background:#faf8f2; border:1px solid #ece6d6; border-radius:8px;">
        <tr>
            <td style="padding:12px 18px; font-size:13px; color:#5f5a4e;">Částka k úhradě</td>
            <td style="padding:12px 18px; text-align:right; font-size:20px; font-weight:bold; color:#0F2038; white-space:nowrap;">
                {{ number_format($f->celkem, 0, ',', ' ') }} Kč
            </td>
        </tr>
        <tr>
            <td style="padding:0 18px 12px; font-size:13px; color:#5f5a4e;">Splatnost</td>
            <td style="padding:0 18px 12px; text-align:right; font-size:14px; color:#374151;">
                {{ optional($f->datum_splatnosti)->format('d. m. Y') ?: '—' }}
            </td>
        </tr>
        @if ($f->variabilni_symbol)
            <tr>
                <td style="padding:0 18px 12px; font-size:13px; color:#5f5a4e;">Variabilní symbol</td>
                <td style="padding:0 18px 12px; text-align:right; font-size:14px; color:#374151;">{{ $f->variabilni_symbol }}</td>
            </tr>
        @endif
        @if ($firma->cislo_uctu)
            <tr>
                <td style="padding:0 18px 12px; font-size:13px; color:#5f5a4e;">Číslo účtu</td>
                <td style="padding:0 18px 12px; text-align:right; font-size:14px; color:#374151;">{{ $firma->cislo_uctu }}</td>
            </tr>
        @endif
    </table>

    @if ($firma->cislo_uctu && $f->celkem > 0)
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 4px;">
            <tr>
                <td style="padding-right:16px; vertical-align:middle;">
                    <img src="{{ route('qr.faktura', ['faktura' => $f->id, 'token' => \App\Support\QrPlatba::token('faktura', $f->id)]) }}"
                         alt="QR platba" width="150" height="150"
                         style="width:150px; height:150px; border:1px solid #ece6d6; border-radius:6px; background:#fff;">
                </td>
                <td style="vertical-align:middle; font-size:13px; color:#5f5a4e; line-height:1.5;">
                    <strong style="color:#0F2038;">QR platba</strong><br>
                    Naskenujte v bankovní aplikaci –<br>
                    částka i variabilní symbol se vyplní samy.
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:14px 0 0;">Děkujeme,<br>{{ $firma->nazev }}</p>
</x-mail-layout>
