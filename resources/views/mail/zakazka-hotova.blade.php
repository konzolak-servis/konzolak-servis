<x-mail-layout :firma="$firma" nadpis="Vaše zakázka je hotová 🎮">
    <p style="margin:0 0 14px;">Dobrý den,</p>

    <p style="margin:0 0 14px;">
        vaše zakázka <strong>{{ $z->cislo }}</strong>@if ($z->zarizeni) – {{ $z->zarizeni->oznaceni }}@endif
        je <strong style="color:#0F2038;">hotová a připravená k vyzvednutí</strong>.
    </p>

    @if ($z->cena_celkem > 0)
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
               style="margin:18px 0; background:#faf8f2; border:1px solid #ece6d6; border-radius:8px;">
            <tr>
                <td style="padding:14px 18px; font-size:14px; color:#5f5a4e;">
                    K úhradě při vyzvednutí
                    @if ($z->zaloha > 0)<br><span style="font-size:12px;">(záloha {{ number_format($z->zaloha, 0, ',', ' ') }} Kč již uhrazena)</span>@endif
                </td>
                <td style="padding:14px 18px; text-align:right; font-size:20px; font-weight:bold; color:#0F2038; white-space:nowrap;">
                    {{ number_format(max($z->cena_celkem - $z->zaloha, 0), 0, ',', ' ') }} Kč
                </td>
            </tr>
        </table>
    @endif

    @if ($z->zaruka_mesice)
        <p style="margin:0 0 14px; font-size:13px; color:#6b7280;">
            Na provedenou opravu se vztahuje záruka <strong>{{ $z->zaruka_mesice }}</strong>
            {{ $z->zaruka_mesice == 1 ? 'měsíc' : ($z->zaruka_mesice < 5 ? 'měsíce' : 'měsíců') }}.
        </p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
           style="margin:18px 0 6px; background:#f6f8fb; border:1px solid #e2e8f0; border-left:3px solid #0F2038; border-radius:8px;">
        <tr>
            <td style="padding:14px 18px;">
                <div style="font-size:13px; font-weight:bold; text-transform:uppercase; letter-spacing:.04em; color:#0F2038; margin-bottom:8px;">
                    Vyzvednutí zařízení
                </div>
                @if ($firma->email_vyzvednuti)
                    <div style="font-size:14px; color:#374151; line-height:1.6;">
                        {!! nl2br(e($firma->email_vyzvednuti)) !!}
                    </div>
                @endif
                @if ($firma->telefon)
                    <div style="font-size:14px; color:#0F2038; margin-top:8px;">
                        <strong>Tel.: {{ $firma->telefon }}</strong>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin:18px 0 0;">S pozdravem,<br>{{ $firma->nazev }}</p>
</x-mail-layout>
