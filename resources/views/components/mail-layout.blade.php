@props(['firma', 'nadpis' => null])
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <title>{{ $nadpis ?? ($firma->nazev ?? 'Konzolák Zlín') }}</title>
</head>
<body style="margin:0; padding:0; background:#f2f1ee; -webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f1ee;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                       style="width:600px; max-width:100%; background:#ffffff; border-radius:10px; overflow:hidden;
                              border:1px solid #e6e2d8; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

                    {{-- hlavička --}}
                    <tr>
                        <td style="background:#0F2038; padding:28px 28px 24px; text-align:center; border-bottom:2px solid #C8992E;">
                            <img src="{{ rtrim(config('app.url'), '/') }}/images/konzolak-logo-print.png"
                                 alt="{{ $firma->nazev ?? 'Konzolák Zlín' }}" height="72"
                                 style="height:72px; width:auto; display:inline-block; color:#E8C77C; font-size:24px; font-weight:bold;">
                        </td>
                    </tr>

                    {{-- obsah --}}
                    <tr>
                        <td style="padding:30px 32px 24px; color:#1f2937; font-size:15px; line-height:1.55;">
                            @isset($nadpis)
                                <h1 style="margin:0 0 16px; font-size:19px; color:#0F2038;">{{ $nadpis }}</h1>
                            @endisset
                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- patička --}}
                    <tr>
                        <td style="padding:18px 32px 26px; border-top:1px solid #eceae3; color:#8a8578; font-size:12px; line-height:1.5;">
                            <strong style="color:#5f5a4e;">{{ $firma->nazev ?? 'Konzolák Zlín' }}</strong><br>
                            @if ($firma->ulice ?? null){{ $firma->ulice }}, {{ trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? '')) }}<br>@endif
                            @if ($firma->telefon ?? null)tel. {{ $firma->telefon }}@endif
                            @if (($firma->telefon ?? null) && ($firma->email ?? null)) · @endif
                            @if ($firma->email ?? null){{ $firma->email }}@endif
                            @if ($firma->web ?? null)<br>{{ $firma->web }}@endif
                        </td>
                    </tr>
                </table>

                <div style="color:#a8a494; font-size:11px; padding:14px 0 0; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                    Tento e-mail byl odeslán automaticky ze servisního systému {{ $firma->nazev ?? 'Konzolák Zlín' }}.
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
